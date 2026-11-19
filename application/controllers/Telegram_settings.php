<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Telegram settings controller (Integrations → Telegram).
 *
 * Configures the Telegram bot that receives appointment notifications. Plain form posts (with the CSRF token) that
 * redirect back to the page with a message.
 *
 * @package Controllers
 */
class Telegram_settings extends EA_Controller
{
    private const EVENTS = ['new', 'changed', 'cancelled', 'requests'];

    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');
        $this->load->library('telegram_notifier');
    }

    /**
     * Render the page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('telegram_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $events = [];

        foreach (self::EVENTS as $event) {
            $events[$event] = setting('telegram_notify_' . $event, '1') === '1';
        }

        html_vars([
            'page_title' => lang('telegram'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'telegram_enabled' => setting('telegram_enabled') === '1',
            'telegram_has_token' => setting('telegram_bot_token', '') !== '',
            'telegram_chat_ids' => (string) setting('telegram_chat_ids', ''),
            'telegram_events' => $events,
            'found_chats' => $this->session->flashdata('telegram_found_chats'),
            'flash_success' => $this->session->flashdata('telegram_success'),
            'flash_error' => $this->session->flashdata('telegram_error'),
        ]);

        $this->load->view('pages/telegram_settings');
    }

    /**
     * Save the settings. An empty token field keeps the saved token.
     */
    public function save(): void
    {
        $this->authorize_post();

        $token = trim((string) request('telegram_bot_token'));

        if ($token !== '') {
            if (!preg_match('/^\d+:[\w-]+$/', $token)) {
                $this->back_with_error(lang('telegram_invalid_token'));

                return;
            }

            setting(['telegram_bot_token' => $token]);
        }

        if (request('remove_token')) {
            setting(['telegram_bot_token' => '']);
        }

        $settings = [
            'telegram_enabled' => request('telegram_enabled') ? '1' : '0',
            'telegram_chat_ids' => implode("\n", $this->telegram_notifier->chat_ids((string) request('telegram_chat_ids'))),
        ];

        foreach (self::EVENTS as $event) {
            $settings['telegram_notify_' . $event] = request('telegram_notify_' . $event) ? '1' : '0';
        }

        setting($settings);

        $this->session->set_flashdata('telegram_success', lang('settings_saved'));

        redirect('telegram_settings');
    }

    /**
     * Send a test message to the saved chats.
     */
    public function test(): void
    {
        $this->authorize_post();

        $chat_ids = $this->telegram_notifier->chat_ids();

        if (setting('telegram_bot_token', '') === '' || empty($chat_ids)) {
            $this->back_with_error(lang('telegram_save_first'));

            return;
        }

        $results = $this->telegram_notifier->send(
            '✅ ' . setting('company_name') . ' - ' . lang('telegram_test_message'),
        );

        $failed = array_filter($results, fn($result) => $result !== true);

        if (empty($failed)) {
            $this->session->set_flashdata('telegram_success', lang('telegram_test_sent'));
        } else {
            $lines = [];

            foreach ($failed as $chat_id => $error) {
                $lines[] = $chat_id . ': ' . $error;
            }

            $this->session->set_flashdata('telegram_error', lang('telegram_test_failed') . ' ' . implode(', ', $lines));
        }

        redirect('telegram_settings');
    }

    /**
     * List the chats that wrote to the bot recently, so the admin can copy their chat IDs.
     */
    public function find_chats(): void
    {
        $this->authorize_post();

        $token = trim((string) request('telegram_bot_token')) ?: (string) setting('telegram_bot_token', '');

        try {
            $chats = $this->telegram_notifier->recent_chats($token);
        } catch (Throwable $e) {
            $this->back_with_error(lang('telegram_find_chats_failed') . ' ' . $e->getMessage());

            return;
        }

        if (empty($chats)) {
            $this->back_with_error(lang('telegram_no_chats_found'));

            return;
        }

        $this->session->set_flashdata('telegram_found_chats', $chats);

        redirect('telegram_settings');
    }

    private function authorize_post(): void
    {
        method('post');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }
    }

    private function back_with_error(string $message): void
    {
        $this->session->set_flashdata('telegram_error', $message);

        redirect('telegram_settings');
    }
}
