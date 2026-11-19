<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Kakao settings controller (Integrations → 카카오).
 *
 * The KakaoTalk share button on the booking completed page needs a Kakao app: its JavaScript key (saved here as the
 * "kakao_javascript_key" setting, else config.php KAKAO_JAVASCRIPT_KEY) and this site's domain registered as a Web
 * platform in Kakao Developers. The page shows a checklist of that setup and whether the key is set.
 *
 * @package Controllers
 */
class Kakao_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');
    }

    /**
     * The JavaScript key in use: the saved setting, else the config.php constant.
     */
    public static function javascript_key(): string
    {
        $key = trim((string) setting('kakao_javascript_key', ''));

        if ($key === '' && defined('Config::KAKAO_JAVASCRIPT_KEY')) {
            $key = (string) Config::KAKAO_JAVASCRIPT_KEY;
        }

        return $key;
    }

    /**
     * Customer Kakao login mode: "off", "optional" or "required".
     */
    public static function login_mode(): string
    {
        $mode = setting('kakao_login_mode', 'off');

        return in_array($mode, ['off', 'optional', 'required'], true) ? $mode : 'off';
    }

    /**
     * Render the page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('kakao_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $site = parse_url(config('base_url'));

        html_vars([
            'page_title' => lang('kakao'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'kakao_key' => self::javascript_key(),
            'kakao_key_from_config' => trim((string) setting('kakao_javascript_key', '')) === '' &&
                self::javascript_key() !== '',
            'site_domain' => ($site['scheme'] ?? 'https') . '://' . ($site['host'] ?? ''),
            'login_mode' => self::login_mode(),
            'rest_api_key' => (string) setting('kakao_rest_api_key', ''),
            'has_client_secret' => setting('kakao_client_secret', '') !== '',
            'redirect_uri' => trusted_site_url('kakao_login/callback'),
            'flash_success' => $this->session->flashdata('kakao_success'),
            'flash_error' => $this->session->flashdata('kakao_error'),
        ]);

        $this->load->view('pages/kakao_settings');
    }

    /**
     * Save the JavaScript key (plain form post).
     */
    public function save(): void
    {
        method('post');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $key = trim((string) request('kakao_javascript_key'));

        // Kakao app keys are 32 hexadecimal characters.
        if ($key !== '' && !preg_match('/^[0-9a-f]{32}$/i', $key)) {
            $this->session->set_flashdata('kakao_error', lang('kakao_invalid_key'));

            redirect('kakao_settings');

            return;
        }

        $rest_api_key = trim((string) request('kakao_rest_api_key'));

        if ($rest_api_key !== '' && !preg_match('/^[0-9a-f]{32}$/i', $rest_api_key)) {
            $this->session->set_flashdata('kakao_error', lang('kakao_invalid_rest_key'));

            redirect('kakao_settings');

            return;
        }

        $login_mode = in_array(request('kakao_login_mode'), ['off', 'optional', 'required'], true)
            ? request('kakao_login_mode')
            : 'off';

        // Customer login needs the REST API key.
        if ($login_mode !== 'off' && $rest_api_key === '') {
            $this->session->set_flashdata('kakao_error', lang('kakao_login_needs_rest_key'));

            redirect('kakao_settings');

            return;
        }

        setting([
            'kakao_javascript_key' => $key,
            'kakao_rest_api_key' => $rest_api_key,
            'kakao_login_mode' => $login_mode,
        ]);

        // The client secret is only replaced when a new one is typed (it is never shown again).
        $client_secret = trim((string) request('kakao_client_secret'));

        if ($client_secret !== '') {
            setting(['kakao_client_secret' => $client_secret]);
        }

        if (request('remove_client_secret')) {
            setting(['kakao_client_secret' => '']);
        }

        $this->session->set_flashdata('kakao_success', lang('settings_saved'));

        redirect('kakao_settings');
    }
}
