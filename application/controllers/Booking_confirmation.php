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
 * Booking confirmation controller.
 *
 * Handles the booking confirmation related operations.
 *
 * @package Controllers
 */
class Booking_confirmation extends EA_Controller
{
    /**
     * Booking_confirmation constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');

        $this->load->library('google_sync');
        $this->load->library('appointment_summary');
    }

    /**
     * Display the appointment registration success page.
     *
     * @throws Exception
     */
    public function of(): void
    {
        $appointment_hash = $this->uri->segment(3);

        allow_iframe_embedding(); // Reached from the booking page which may be embedded in an iframe.

        $occurrences = $this->appointments_model->get(['hash' => $appointment_hash]);

        if (empty($occurrences)) {
            redirect('appointments'); // The appointment does not exist.

            return;
        }

        $appointment = $occurrences[0];

        $add_to_google_url = $this->google_sync->get_add_to_google_url($appointment['id']);

        html_vars([
            // Integrations → 카카오 (kakao_javascript_key setting), else config.php KAKAO_JAVASCRIPT_KEY.
            'kakao_javascript_key' =>
                trim((string) setting('kakao_javascript_key', '')) ?:
                (defined('Config::KAKAO_JAVASCRIPT_KEY') ? Config::KAKAO_JAVASCRIPT_KEY : ''),
            'appointment_share_text' => $this->appointment_summary->text($appointment),
            'booking_url' => site_url(),
            // Same customer link as in the confirmation email: shows the appointment and allows changing it.
            'appointment_manage_url' => trusted_site_url('booking/reschedule/' . $appointment['hash']),
            'page_title' => lang('success'),
            'call_button_page' => 'confirmation',
            'kakao_logged_in' => !empty(session('kakao_user')) && setting('kakao_login_mode', 'off') !== 'off',
            'company_color' => setting('company_color'),
            'google_analytics_code' => setting('google_analytics_code'),
            'matomo_analytics_url' => setting('matomo_analytics_url'),
            'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
            'add_to_google_url' => $add_to_google_url,
            'display_add_to_google_calendar' => setting('display_add_to_google_calendar', '1'),
        ]);

        $this->load->view('pages/booking_confirmation');
    }
}
