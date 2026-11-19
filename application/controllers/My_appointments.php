<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * My appointments controller ("내 예약", customers logged in with Kakao).
 *
 * Shows the Kakao user's upcoming appointments (with a link to check / change each one, which follows the
 * "customer_change_mode" setting) and their visit history. Their appointments are those of every customer record
 * linked to the Kakao account (see Kakao_auth::customer_ids).
 *
 * Not logged in: a page with the Kakao login button that comes back here. Kakao login off: the booking page.
 *
 * @package Controllers
 */
class My_appointments extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('kakao_auth');
        $this->load->library('appointment_summary');
        $this->load->model('appointments_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
    }

    public function index(): void
    {
        method('get');

        if ($this->kakao_auth->mode() === 'off') {
            redirect('');

            return;
        }

        $common = [
            'company_color' => setting('company_color'),
            'google_analytics_code' => setting('google_analytics_code'),
            'matomo_analytics_url' => setting('matomo_analytics_url'),
            'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
            'call_button_page' => 'status',
        ];

        $user = $this->kakao_auth->current_user();

        if (!$user) {
            html_vars($common + ['page_title' => lang('my_appointments'), 'logged_in' => false]);

            $this->load->view('pages/my_appointments');

            return;
        }

        $customer_ids = $this->kakao_auth->customer_ids($user);

        $records = empty($customer_ids)
            ? []
            : $this->db
                ->where_in('id_users_customer', $customer_ids)
                ->where('is_unavailability', 0)
                ->order_by('start_datetime', 'DESC')
                ->limit(200)
                ->get('appointments')
                ->result_array();

        $now = date('Y-m-d H:i:s');
        $upcoming = [];
        $history = [];

        $this->load->model('appointment_change_requests_model');

        foreach ($records as $record) {
            $item = $this->describe($record);

            if ($record['end_datetime'] >= $now) {
                $item['pending_request'] = $this->appointment_change_requests_model->find_pending((int) $record['id']);
                $upcoming[] = $item;
            } else {
                $history[] = $item;
            }
        }

        // Upcoming: soonest first. History: newest first (as queried).
        $upcoming = array_reverse($upcoming);

        $visits = count(array_filter($history, fn($item) => !$item['is_cancelled']));

        html_vars(
            $common + [
                'page_title' => lang('my_appointments'),
                'logged_in' => true,
                'display_name' => ($user['name'] ?? '') ?: (($user['nickname'] ?? '') ?: lang('kakao_user')),
                'upcoming' => $upcoming,
                'history' => $history,
                'visit_count' => $visits,
                'last_visit' => $history[0]['date_text'] ?? null,
            ],
        );

        $this->load->view('pages/my_appointments');
    }

    /**
     * The texts of one appointment card / history row.
     */
    private function describe(array $record): array
    {
        $rows = $this->appointment_summary->rows($record, false);
        $status = (string) ($record['status'] ?? '');

        return [
            'hash' => $record['hash'],
            'date_text' => $this->appointment_summary->format_datetime($record['start_datetime']),
            'service' => $rows['서비스'] ?? '-',
            'provider' => $rows['담당자'] ?? '-',
            'price' => $rows['가격'] ?? '',
            'location' => $rows['장소'] ?? '',
            'status' => $status,
            'is_cancelled' => (bool) preg_match('/취소|cancel/iu', $status),
            'manage_url' => site_url('booking/reschedule/' . $record['hash']),
        ];
    }
}
