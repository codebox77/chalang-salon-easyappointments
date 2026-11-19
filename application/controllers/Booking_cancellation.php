<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking cancellation controller.
 *
 * Handles the booking cancellation related operations.
 *
 * @package Controllers
 */
class Booking_cancellation extends EA_Controller
{
    /**
     * Booking_cancellation constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');

        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('webhooks_client');
    }

    /**
     * Cancel an existing appointment.
     *
     * This method removes an appointment from the company's schedule. In order for the appointment to be deleted, the
     * hash string must be provided. The customer can only cancel the appointment if the edit time period is not over
     * yet.
     *
     * @param string $appointment_hash This appointment hash identifier.
     */
    public function of(string $appointment_hash): void
    {
        try {
            allow_iframe_embedding(); // Reached from the booking page which may be embedded in an iframe.

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            // Validate hash format to prevent injection
            if (!preg_match('/^[a-zA-Z0-9]+$/', $appointment_hash)) {
                abort(400, 'Invalid appointment hash format.');
            }

            // The method check comes first, so that a plain GET is refused before any parameter validation can leak an
            // internal message to the visitor.
            if ($this->input->method() !== 'post') {
                abort(403, 'Forbidden');
            }

            check('cancellation_reason', 'string');

            $cancellation_reason = request('cancellation_reason');

            if (empty($cancellation_reason)) {
                abort(403, 'Forbidden');
            }

            // Apply rate limiting for cancellations (5 per 10 minutes per IP)
            $this->apply_cancellation_rate_limit();

            // Sanitize cancellation reason (limit length and strip dangerous content)
            $cancellation_reason = strip_tags(substr(trim($cancellation_reason), 0, 1000));

            $occurrences = $this->appointments_model->get(['hash' => $appointment_hash]);

            if (empty($occurrences)) {
                html_vars([
                    'page_title' => lang('appointment_not_found'),
                    'company_color' => setting('company_color'),
                    'message_title' => lang('appointment_not_found'),
                    'message_text' => lang('appointment_does_not_exist_in_db'),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => setting('google_analytics_code'),
                    'matomo_analytics_url' => setting('matomo_analytics_url'),
                    'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
                    'display_login_button' => setting('display_login_button'),
                ]);

                $this->load->view('pages/booking_message');

                return;
            }

            $appointment = $occurrences[0];

            // Customer change mode (booking settings): "view_only" does not allow cancelling via the link, and
            // "approval" stores a cancellation request that a provider or admin approves later.
            $this->load->model('appointment_change_requests_model');

            $change_mode = Appointment_change_requests_model::mode();

            // The change period is over: no cancellation through the link (the status page already hides the button).
            if ($this->appointment_change_requests_model->is_locked($appointment)) {
                html_vars([
                    'page_title' => lang('appointment_locked'),
                    'company_color' => setting('company_color'),
                    'message_title' => lang('appointment_locked'),
                    'message_text' => lang('appointment_change_period_over'),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => setting('google_analytics_code'),
                    'matomo_analytics_url' => setting('matomo_analytics_url'),
                    'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
                    'display_login_button' => setting('display_login_button'),
                ]);

                $this->load->view('pages/booking_message');

                return;
            }

            if ($change_mode !== Appointment_change_requests_model::MODE_SELF) {
                $is_request = $change_mode === Appointment_change_requests_model::MODE_APPROVAL;

                if ($is_request) {
                    $request_id = $this->appointment_change_requests_model->create([
                        'id_appointments' => $appointment['id'],
                        'type' => Appointment_change_requests_model::TYPE_CANCEL,
                        'notes' => $cancellation_reason,
                    ]);

                    try {
                        $this->load->library('telegram_notifier');
                        $this->telegram_notifier->notify_change_request(
                            $appointment,
                            $this->appointment_change_requests_model->find($request_id),
                        );
                    } catch (Throwable $e) {
                        log_message('error', 'Telegram cancellation request notification failed: ' . $e->getMessage());
                    }
                }

                html_vars([
                    'page_title' => $is_request ? lang('cancellation_requested') : lang('appointment_change_not_allowed'),
                    'company_color' => setting('company_color'),
                    'message_title' => $is_request
                        ? lang('cancellation_requested')
                        : lang('appointment_change_not_allowed'),
                    'message_text' => $is_request
                        ? lang('cancellation_requested_message')
                        : lang('appointment_change_not_allowed_message'),
                    'message_icon' => base_url('assets/img/error.png'),
                    'message_fa_icon' => $is_request ? 'fas fa-check-circle' : null,
                    'google_analytics_code' => setting('google_analytics_code'),
                    'matomo_analytics_url' => setting('matomo_analytics_url'),
                    'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
                    'display_login_button' => setting('display_login_button'),
                ]);

                $this->load->view('pages/booking_message');

                return;
            }

            $provider = $this->providers_model->find($appointment['id_users_provider']);

            $customer = $this->customers_model->find($appointment['id_users_customer']);

            $service = $this->services_model->find($appointment['id_services']);

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_email' => setting('company_email'),
                'company_link' => setting('company_link'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->appointments_model->delete($appointment['id']);

            $this->synchronization->sync_appointment_deleted($appointment, $provider);

            $this->notifications->notify_appointment_deleted(
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                $cancellation_reason,
            );

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);

            html_vars([
                'page_title' => lang('appointment_cancelled_title'),
                'company_color' => setting('company_color'),
                'google_analytics_code' => setting('google_analytics_code'),
                'matomo_analytics_url' => setting('matomo_analytics_url'),
                'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
            ]);

            $this->load->view('pages/booking_cancellation');
        } catch (Throwable $e) {
            log_message('error', 'Booking Cancellation Exception: ' . $e->getMessage());

            // The cancellation failed, so the visitor must not be told that the appointment was cancelled, and the
            // internal exception message must not reach them either.
            html_vars([
                'page_title' => lang('unexpected_issues'),
                'company_color' => setting('company_color'),
                'message_title' => lang('unexpected_issues'),
                'message_text' => lang('unexpected_issues_message'),
                'message_icon' => base_url('assets/img/error.png'),
                'google_analytics_code' => setting('google_analytics_code'),
                'matomo_analytics_url' => setting('matomo_analytics_url'),
                'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
                'display_login_button' => setting('display_login_button'),
            ]);

            $this->load->view('pages/booking_message');
        }
    }

    /**
     * Apply rate limiting for cancellation attempts.
     *
     * @throws RuntimeException If rate limit is exceeded.
     */
    private function apply_cancellation_rate_limit(): void
    {
        try {
            $this->load->driver('cache', ['adapter' => 'file']);

            if (!isset($this->cache) || !is_object($this->cache)) {
                log_message('debug', 'Cache driver not available, skipping cancellation rate limit check.');
                return;
            }

            $ip = $this->input->ip_address();
            $cache_key = 'cancellation_attempts_' . str_replace([':', '.'], '_', $ip);

            $attempts = $this->cache->get($cache_key);

            if ($attempts === false) {
                $this->cache->save($cache_key, 1, 600); // 10 minutes
                return;
            }

            $this->cache->save($cache_key, $attempts + 1, 600);

            if ($attempts >= 5) {
                log_message('error', 'Cancellation rate limit exceeded for IP: ' . $ip);
                throw new RuntimeException('Too many cancellation attempts. Please try again later.');
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            log_message('error', 'Cache error in cancellation rate limiting: ' . $e->getMessage());
        }
    }
}
