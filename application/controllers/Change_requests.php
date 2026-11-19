<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Change requests controller.
 *
 * Lists the reschedule and cancellation requests that customers made through their appointment link (when the
 * "customer_change_mode" booking setting is "approval"), and lets providers, secretaries and admins approve or
 * reject them. Providers only see requests for their own appointments, secretaries those of their providers.
 *
 * @package Controllers
 */
class Change_requests extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointment_change_requests_model');
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');

        $this->load->library('accounts');
        $this->load->library('appointment_summary');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('webhooks_client');
    }

    /**
     * Render the change requests page.
     */
    public function index(): void
    {
        method('get');

        $user_id = session('user_id');

        if (cannot('view', PRIV_APPOINTMENTS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $provider_ids = $this->appointment_change_requests_model->visible_provider_ids();

        $pending = $this->appointment_change_requests_model->get_list(
            [Appointment_change_requests_model::STATUS_PENDING],
            $provider_ids,
        );

        $handled = $this->appointment_change_requests_model->get_list(
            [Appointment_change_requests_model::STATUS_APPROVED, Appointment_change_requests_model::STATUS_REJECTED],
            $provider_ids,
            20,
        );

        html_vars([
            'page_title' => lang('change_requests'),
            'active_menu' => 'change_requests',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'pending_requests' => array_map([$this, 'describe'], $pending),
            'handled_requests' => array_map([$this, 'describe'], $handled),
            'change_mode' => Appointment_change_requests_model::mode(),
            'can_edit' => can('edit', PRIV_APPOINTMENTS),
            'flash_success' => $this->session->flashdata('change_requests_success'),
            'flash_error' => $this->session->flashdata('change_requests_error'),
        ]);

        $this->load->view('pages/change_requests');
    }

    /**
     * Approve a request: apply the reschedule, or delete the appointment for a cancellation.
     */
    public function approve(): void
    {
        $this->handle(true);
    }

    /**
     * Reject a request: the appointment stays as it is.
     */
    public function reject(): void
    {
        $this->handle(false);
    }

    /**
     * Approve or reject the posted request, then go back to the list with a message.
     */
    private function handle(bool $approve): void
    {
        method('post');

        if (cannot('edit', PRIV_APPOINTMENTS)) {
            abort(403, 'Forbidden');
        }

        try {
            $request = $this->appointment_change_requests_model->find((int) request('request_id'));

            if (empty($request) || $request['status'] !== Appointment_change_requests_model::STATUS_PENDING) {
                throw new RuntimeException(lang('change_request_not_found'));
            }

            $appointment = $this->appointments_model->get(['id' => $request['id_appointments']])[0] ?? null;

            if (empty($appointment)) {
                throw new RuntimeException(lang('change_request_not_found'));
            }

            $provider_ids = $this->appointment_change_requests_model->visible_provider_ids();

            if ($provider_ids !== null && !in_array((int) $appointment['id_users_provider'], $provider_ids, true)) {
                abort(403, 'Forbidden');
            }

            if ($approve) {
                if ($request['type'] === Appointment_change_requests_model::TYPE_CANCEL) {
                    $this->apply_cancellation($appointment, (string) $request['notes']);
                } else {
                    $this->apply_reschedule($appointment, $request);
                }
            }

            $this->appointment_change_requests_model->set_status(
                (int) $request['id'],
                $approve
                    ? Appointment_change_requests_model::STATUS_APPROVED
                    : Appointment_change_requests_model::STATUS_REJECTED,
                (int) session('user_id'),
            );

            $this->session->set_flashdata(
                'change_requests_success',
                $approve ? lang('change_request_approved') : lang('change_request_rejected_admin'),
            );
        } catch (Throwable $e) {
            log_message('error', 'Change request handling failed: ' . $e->getMessage());

            $this->session->set_flashdata('change_requests_error', $e->getMessage());
        }

        redirect('change_requests');
    }

    /**
     * Move the appointment to the requested time (and service / provider).
     *
     * @throws RuntimeException When the requested time is no longer free.
     */
    private function apply_reschedule(array $appointment, array $request): void
    {
        $provider_id = (int) $request['id_users_provider'];

        // The time was free when the customer asked, but may have been booked since.
        if (
            $this->appointments_model->has_provider_conflict(
                $provider_id,
                $request['start_datetime'],
                $request['end_datetime'],
                (int) $appointment['id'],
            )
        ) {
            throw new RuntimeException(lang('change_request_conflict'));
        }

        $appointment['start_datetime'] = $request['start_datetime'];
        $appointment['end_datetime'] = $request['end_datetime'];
        $appointment['id_services'] = (int) $request['id_services'];
        $appointment['id_users_provider'] = $provider_id;

        if (!empty($request['notes'])) {
            $appointment['notes'] = $request['notes'];
        }

        $this->appointments_model->save($appointment);

        $appointment = $this->appointments_model->find($appointment['id']);
        $service = $this->services_model->find($appointment['id_services']);
        $provider = $this->providers_model->find($appointment['id_users_provider']);
        $customer = $this->customers_model->find($appointment['id_users_customer']);
        $settings = $this->notification_settings();

        $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

        $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, true);

        $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);
    }

    /**
     * Delete the appointment (approved cancellation request).
     */
    private function apply_cancellation(array $appointment, string $reason): void
    {
        $service = $this->services_model->find($appointment['id_services']);
        $provider = $this->providers_model->find($appointment['id_users_provider']);
        $customer = $this->customers_model->find($appointment['id_users_customer']);

        $this->appointments_model->delete($appointment['id']);

        $this->synchronization->sync_appointment_deleted($appointment, $provider);

        $this->notifications->notify_appointment_deleted(
            $appointment,
            $service,
            $provider,
            $customer,
            $this->notification_settings(),
            $reason,
        );

        $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);
    }

    /**
     * Settings used by the notification emails.
     */
    private function notification_settings(): array
    {
        $company_color = setting('company_color');

        return [
            'company_name' => setting('company_name'),
            'company_link' => setting('company_link'),
            'company_email' => setting('company_email'),
            'company_color' =>
                !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];
    }

    /**
     * Add the display texts (current appointment, requested change, customer) to a request row.
     */
    private function describe(array $request): array
    {
        $current = $this->appointment_summary->rows([
            'id_services' => $request['current_id_services'],
            'id_users_provider' => $request['current_id_users_provider'],
            'start_datetime' => $request['current_start_datetime'],
            'id_users_customer' => $request['id_users_customer'],
        ]);

        $request['customer_name'] = $current['예약자'] ?? '';
        $request['current_rows'] = array_intersect_key($current, array_flip(['서비스', '담당자', '일시']));

        $request['requested_rows'] =
            $request['type'] === Appointment_change_requests_model::TYPE_RESCHEDULE
                ? array_intersect_key(
                    $this->appointment_summary->rows(
                        [
                            'id_services' => $request['id_services'],
                            'id_users_provider' => $request['id_users_provider'],
                            'start_datetime' => $request['start_datetime'],
                        ],
                        false,
                    ),
                    array_flip(['서비스', '담당자', '일시']),
                )
                : [];

        try {
            $request['customer_phone'] = $this->customers_model->find((int) $request['id_users_customer'])['phone_number'] ?? '';
        } catch (Throwable) {
            $request['customer_phone'] = '';
        }

        $request['created_text'] = $this->appointment_summary->format_datetime($request['create_datetime']);

        return $request;
    }
}
