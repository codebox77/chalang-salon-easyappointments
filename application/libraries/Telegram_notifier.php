<?php defined('BASEPATH') or exit('No direct script access allowed');

use GuzzleHttp\Client;

/**
 * Telegram notifier library.
 *
 * Sends appointment notifications to one or more Telegram chats through a bot (Integrations → Telegram).
 *
 * Settings: telegram_enabled, telegram_bot_token, telegram_chat_ids (one per line or comma separated) and the
 * event switches telegram_notify_new / _changed / _cancelled / _requests (default on).
 *
 * Sending never throws to the caller: a failing Telegram call must not break booking. Failures are logged.
 *
 * @package Libraries
 */
class Telegram_notifier
{
    private const API_URL = 'https://api.telegram.org/bot';

    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->library('appointment_summary');
        $this->CI->load->model('customers_model');
    }

    /**
     * Whether notifications are on, configured and wanted for the given event.
     *
     * @param string $event new, changed, cancelled or requests.
     */
    public function is_enabled(string $event): bool
    {
        return setting('telegram_enabled') === '1' &&
            setting('telegram_bot_token', '') !== '' &&
            !empty($this->chat_ids()) &&
            setting('telegram_notify_' . $event, '1') === '1';
    }

    /**
     * Parse the chat IDs setting (or the given text).
     *
     * @return string[]
     */
    public function chat_ids(?string $text = null): array
    {
        $parts = preg_split('/[\s,]+/', (string) ($text ?? setting('telegram_chat_ids', '')));

        return array_values(array_filter($parts, fn($id) => preg_match('/^-?\d+$|^@\w+$/', $id)));
    }

    /**
     * New or changed appointment (called from Notifications::notify_appointment_saved).
     */
    public function notify_saved(array $appointment, array $customer, bool $changed): void
    {
        $event = $changed ? 'changed' : 'new';

        if (!$this->is_enabled($event)) {
            return;
        }

        $title = $changed ? '🔄 예약 변경' : '🆕 새 예약';

        $this->send_safely(
            $this->build_message($title, $appointment, $customer, [
                '관리' => trusted_site_url('calendar/reschedule/' . $appointment['hash']),
            ]),
        );
    }

    /**
     * Cancelled (deleted) appointment (called from Notifications::notify_appointment_deleted).
     */
    public function notify_cancelled(array $appointment, array $customer, string $reason = ''): void
    {
        if (!$this->is_enabled('cancelled')) {
            return;
        }

        $extra = $reason !== '' ? ['취소 사유' => $reason] : [];

        $this->send_safely($this->build_message('❌ 예약 취소', $appointment, $customer, $extra));
    }

    /**
     * Customer change or cancellation request waiting for approval.
     */
    public function notify_change_request(array $appointment, array $request): void
    {
        if (!$this->is_enabled('requests')) {
            return;
        }

        try {
            $customer = $this->CI->customers_model->find((int) $appointment['id_users_customer']);
        } catch (Throwable) {
            $customer = [];
        }

        if ($request['type'] === 'cancel') {
            $title = '🙋 취소 요청 (승인 필요)';
            $extra = ['취소 사유' => (string) ($request['notes'] ?? '')];
        } else {
            $title = '🙋 변경 요청 (승인 필요)';
            $requested = $this->CI->appointment_summary->rows(
                [
                    'id_services' => $request['id_services'],
                    'id_users_provider' => $request['id_users_provider'],
                    'start_datetime' => $request['start_datetime'],
                ],
                false,
            );
            $extra = ['요청 일시' => $requested['일시'], '요청 서비스' => $requested['서비스']];
        }

        $extra['승인하기'] = trusted_site_url('change_requests');

        $this->send_safely($this->build_message($title, $appointment, $customer, $extra));
    }

    /**
     * Send a text to every configured chat (or the given ones), returning the outcome per chat.
     *
     * @param string $text
     * @param string|null $token Bot token (default: the saved one).
     * @param string[]|null $chat_ids Chats (default: the saved ones).
     *
     * @return array chat id => true or the error message.
     */
    public function send(string $text, ?string $token = null, ?array $chat_ids = null): array
    {
        $token ??= (string) setting('telegram_bot_token', '');
        $chat_ids ??= $this->chat_ids();

        $results = [];

        foreach ($chat_ids as $chat_id) {
            try {
                $response = $this->call($token, 'sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => mb_substr($text, 0, 4000),
                    'disable_web_page_preview' => 'true',
                ]);

                $results[$chat_id] = ($response['ok'] ?? false) ? true : ($response['description'] ?? 'error');
            } catch (Throwable $e) {
                $results[$chat_id] = $this->redact($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get the chats that recently wrote to the bot (to find chat IDs).
     *
     * @return array List of ['id' => ..., 'name' => ...].
     *
     * @throws RuntimeException When Telegram refuses the request (e.g. invalid token).
     */
    public function recent_chats(string $token): array
    {
        $response = $this->call($token, 'getUpdates', []);

        if (!($response['ok'] ?? false)) {
            throw new RuntimeException($response['description'] ?? 'Telegram error');
        }

        $chats = [];

        foreach ($response['result'] ?? [] as $update) {
            $chat = $update['message']['chat'] ?? ($update['my_chat_member']['chat'] ?? ($update['channel_post']['chat'] ?? null));

            if (!$chat) {
                continue;
            }

            $name = $chat['title'] ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''));

            $chats[(string) $chat['id']] = ['id' => (string) $chat['id'], 'name' => $name ?: (string) $chat['id']];
        }

        return array_values($chats);
    }

    /**
     * Call a Bot API method and return the decoded JSON (also for 4xx answers, which carry a description).
     */
    private function call(string $token, string $method, array $params): array
    {
        if (!preg_match('/^\d+:[\w-]+$/', $token)) {
            throw new RuntimeException(lang('telegram_invalid_token'));
        }

        $client = new Client(['timeout' => 5, 'connect_timeout' => 3, 'http_errors' => false]);

        $response = $client->post(self::API_URL . $token . '/' . $method, ['form_params' => $params]);

        return json_decode((string) $response->getBody(), true) ?: ['ok' => false, 'description' => 'HTTP ' . $response->getStatusCode()];
    }

    /**
     * Build the message text: title, company, appointment summary, phone number and extra lines.
     */
    private function build_message(string $title, array $appointment, array $customer, array $extra = []): string
    {
        $lines = [$title . ' - ' . setting('company_name'), ''];

        try {
            foreach ($this->CI->appointment_summary->rows($appointment) as $label => $value) {
                $lines[] = $label . ': ' . $value;
            }
        } catch (Throwable) {
            $lines[] = '일시: ' . ($appointment['start_datetime'] ?? '-');
        }

        if (!empty($customer['phone_number'])) {
            $lines[] = '전화: ' . $customer['phone_number'];
        }

        if (!empty($appointment['notes'])) {
            $lines[] = '메모: ' . $appointment['notes'];
        }

        foreach ($extra as $label => $value) {
            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Remove the bot token from a message (HTTP client errors contain the request URL, which holds the token).
     */
    private function redact(string $message): string
    {
        return preg_replace('#/bot[^/\s]+#', '/bot***', $message);
    }

    /**
     * Send and log failures, never throw.
     */
    private function send_safely(string $text): void
    {
        try {
            foreach ($this->send($text) as $chat_id => $result) {
                if ($result !== true) {
                    log_message('error', 'Telegram notification to ' . $chat_id . ' failed: ' . $result);
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Telegram notification failed: ' . $this->redact($e->getMessage()));
        }
    }
}
