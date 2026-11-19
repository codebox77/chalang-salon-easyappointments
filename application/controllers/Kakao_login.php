<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Kakao login controller (customer login on the booking page).
 *
 * kakao_login/start    → Kakao login page (?return=my_appointments to come back to "내 예약")
 * kakao_login/callback → back from Kakao: keep the Kakao user in the session, return to the booking page
 * kakao_login/logout   → forget the Kakao user of this browser session (the next login asks for the Kakao ID)
 * kakao_login/switch_account → log out and log in with another Kakao account
 *
 * @package Controllers
 */
class Kakao_login extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('kakao_auth');
        $this->load->model('customers_model');
    }

    public function start(): void
    {
        if ($this->kakao_auth->mode() === 'off') {
            redirect('');

            return;
        }

        // Where to go after the login: the booking page, or "내 예약" (only these two, no open redirects).
        session(['kakao_return' => request('return') === 'my_appointments' ? 'my_appointments' : '']);

        // After a logout (or "다른 카카오 계정으로 로그인") Kakao asks for the ID again.
        $force_login = request('switch') || $this->kakao_auth->must_force_login();

        header('Location: ' . $this->kakao_auth->authorize_url((bool) $force_login), true, 302);
    }

    /**
     * "다른 카카오 계정으로 로그인": log out here and open the Kakao login page asking for the ID.
     */
    public function switch_account(): void
    {
        $this->kakao_auth->logout();

        $return = request('return') === 'my_appointments' ? '&return=my_appointments' : '';

        redirect('kakao_login/start?switch=1' . $return);
    }

    public function callback(): void
    {
        try {
            if ($this->kakao_auth->mode() === 'off') {
                redirect('');

                return;
            }

            // The customer cancelled on the Kakao page (or Kakao refused): back to the booking page.
            if (request('error')) {
                redirect('');

                return;
            }

            $user = $this->kakao_auth->handle_callback((string) request('code'), (string) request('state'));

            // A returning customer: fill in the details they used last time (name and phone in the booking form).
            $customer_id = $this->kakao_auth->linked_customer_id($user['id']);

            if ($customer_id) {
                try {
                    $customer = $this->customers_model->find($customer_id);

                    $user['customer'] = [
                        'first_name' => (string) ($customer['first_name'] ?? ''),
                        'last_name' => (string) ($customer['last_name'] ?? ''),
                        'phone_number' => (string) ($customer['phone_number'] ?? ''),
                        'email' => (string) ($customer['email'] ?? ''),
                    ];
                } catch (Throwable) {
                    // The customer record was deleted, nothing to fill in.
                }
            }

            $this->kakao_auth->login($user);

            // Refresh the profile photo shown on the admin customers page (best effort).
            try {
                $this->kakao_auth->save_profile_image($user);
            } catch (Throwable $e) {
                log_message('error', 'Kakao profile photo save failed: ' . $e->getMessage());
            }

            $return = session('kakao_return') === 'my_appointments' ? 'my_appointments' : '';

            $this->session->unset_userdata('kakao_return');

            redirect($return);
        } catch (Throwable $e) {
            log_message('error', 'Kakao login failed: ' . $e->getMessage());

            html_vars([
                'page_title' => lang('kakao_login_failed'),
                'company_color' => setting('company_color'),
                'message_title' => lang('kakao_login_failed'),
                'message_text' => lang('kakao_login_failed_message'),
                'message_icon' => base_url('assets/img/error.png'),
                'google_analytics_code' => setting('google_analytics_code'),
                'matomo_analytics_url' => setting('matomo_analytics_url'),
                'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
                'display_login_button' => false,
            ]);

            $this->load->view('pages/booking_message');
        }
    }

    public function logout(): void
    {
        $this->kakao_auth->logout();

        redirect('');
    }
}
