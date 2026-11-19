<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Calendar colors settings controller (Settings → 캘린더 색상).
 *
 * Settings:
 * - calendar_color_mode: "appointment" (the appointment / service color, original behavior) or "provider".
 * - calendar_provider_colors: JSON {provider id: "#rrggbb"}.
 * - calendar_status_styles: JSON {status: {color: "#rrggbb" or "", brightness: "normal" | "light" | "lighter"}}.
 *
 * Applied in the calendar by assets/js/utils/calendar_colors.js.
 *
 * @package Controllers
 */
class Calendar_colors extends EA_Controller
{
    private const BRIGHTNESS = ['normal', 'light', 'lighter'];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('providers_model');

        $this->load->library('accounts');
    }

    /**
     * Render the page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('calendar_colors')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        html_vars([
            'page_title' => lang('calendar_colors'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'color_mode' => setting('calendar_color_mode', 'appointment'),
            'providers' => $this->providers_model->get(),
            'provider_colors' => json_decode((string) setting('calendar_provider_colors', '{}'), true) ?: [],
            'statuses' => json_decode((string) setting('appointment_status_options', '[]'), true) ?: [],
            'status_styles' => json_decode((string) setting('calendar_status_styles', '{}'), true) ?: [],
            'flash_success' => $this->session->flashdata('calendar_colors_success'),
        ]);

        $this->load->view('pages/calendar_colors');
    }

    /**
     * Save the settings (plain form post).
     */
    public function save(): void
    {
        method('post');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $provider_colors = [];

        foreach ((array) request('provider_colors', []) as $provider_id => $color) {
            if (ctype_digit((string) $provider_id) && $this->is_color($color)) {
                $provider_colors[(int) $provider_id] = strtolower($color);
            }
        }

        $statuses = json_decode((string) setting('appointment_status_options', '[]'), true) ?: [];
        $posted_styles = (array) request('status_styles', []);
        $status_styles = [];

        foreach ($statuses as $index => $status) {
            // Indexed by position, so that status names with any characters work as form keys.
            $style = (array) ($posted_styles[$index] ?? []);

            $brightness = in_array($style['brightness'] ?? '', self::BRIGHTNESS, true) ? $style['brightness'] : 'normal';
            $color = !empty($style['use_color']) && $this->is_color($style['color'] ?? '') ? strtolower($style['color']) : '';

            if ($brightness !== 'normal' || $color !== '') {
                $status_styles[$status] = ['color' => $color, 'brightness' => $brightness];
            }
        }

        setting([
            'calendar_color_mode' => request('color_mode') === 'provider' ? 'provider' : 'appointment',
            'calendar_provider_colors' => json_encode((object) $provider_colors),
            'calendar_status_styles' => json_encode((object) $status_styles, JSON_UNESCAPED_UNICODE),
        ]);

        $this->session->set_flashdata('calendar_colors_success', lang('settings_saved'));

        redirect('calendar_colors');
    }

    private function is_color(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }
}
