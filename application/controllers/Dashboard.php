<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dashboard controller (상황판).
 *
 * A full-screen status board for a 16:9 screen in the salon: today's schedule per provider, who is being served now,
 * the next customers and a few numbers. The page polls dashboard/data every 30 seconds.
 *
 * Access:
 * - dashboard          logged-in users who may view appointments.
 * - dashboard/tv/{key} no login, for the salon TV. The key is the "dashboard_token" setting; admins can create a new
 *                      one on the dashboard (the old address then stops working).
 *
 * Customer names are masked (홍길동 → 홍*동) and phone numbers are not included, as customers can see the screen.
 *
 * @package Controllers
 */
class Dashboard extends EA_Controller
{
    private const PALETTE = ['#4e79a7', '#f28e2b', '#59a14f', '#e15759', '#b07aa1', '#76b7b2', '#edc948', '#ff9da7'];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
    }

    /**
     * Dashboard for logged-in users.
     */
    public function index(): void
    {
        if (cannot('view', PRIV_APPOINTMENTS)) {
            if (session('user_id')) {
                abort(403, 'Forbidden');
            }

            session(['dest_url' => site_url('dashboard')]);

            redirect('login');

            return;
        }

        $token = (string) setting('dashboard_token', '');

        $this->render([
            'data_url' => site_url('dashboard/data'),
            'is_tv' => false,
            'can_manage_tv' => can('edit', PRIV_SYSTEM_SETTINGS),
            'tv_url' => $token !== '' ? trusted_site_url('dashboard/tv/' . $token) : '',
        ]);
    }

    /**
     * Dashboard for the salon TV (no login, secret key in the address).
     */
    public function tv(string $token = ''): void
    {
        if (!$this->is_valid_token($token)) {
            abort(404);
        }

        $this->render([
            'data_url' => site_url('dashboard/data') . '?key=' . rawurlencode($token),
            'is_tv' => true,
            'can_manage_tv' => false,
            'tv_url' => '',
        ]);
    }

    /**
     * Create a new TV key (the previous TV address stops working).
     */
    public function regenerate_token(): void
    {
        method('post');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        setting(['dashboard_token' => bin2hex(random_bytes(16))]);

        redirect('dashboard?tv=1');
    }

    /**
     * Today's data as JSON.
     */
    public function data(): void
    {
        try {
            if (cannot('view', PRIV_APPOINTMENTS) && !$this->is_valid_token((string) request('key'))) {
                abort(403, 'Forbidden');
            }

            json_response($this->build_data());
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function render(array $vars): void
    {
        // Only the TV page may be shown inside a frame (e.g. a signage app); the staff page has a form.
        if (!empty($vars['is_tv'])) {
            allow_iframe_embedding();
        }

        html_vars(
            $vars + [
                'company_name' => setting('company_name'),
                'company_logo' => base_url('assets/img/logo.png'),
                'initial_data' => $this->build_data(),
            ],
        );

        $this->load->view('pages/dashboard');
    }

    private function is_valid_token(string $token): bool
    {
        $saved = (string) setting('dashboard_token', '');

        return $saved !== '' && $token !== '' && hash_equals($saved, $token);
    }

    /**
     * Collect today's schedule, the next days' counts and the numbers shown on the board.
     */
    private function build_data(): array
    {
        $timezone = new DateTimeZone(setting('default_timezone', 'Asia/Seoul') ?: 'Asia/Seoul');
        $now = new DateTime('now', $timezone);
        $today = $now->format('Y-m-d');
        $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
        $weekday_keys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        // Providers and their colors (Settings → 캘린더 색상, else a palette color).
        $provider_colors = json_decode((string) setting('calendar_provider_colors', '{}'), true) ?: [];
        $providers = [];

        foreach ($this->providers_model->get() as $index => $provider) {
            $color = $provider_colors[$provider['id']] ?? '';

            $providers[(int) $provider['id']] = [
                'id' => (int) $provider['id'],
                'name' => trim($provider['last_name'] . $provider['first_name']),
                'color' => preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : self::PALETTE[$index % count(self::PALETTE)],
            ];
        }

        // Business hours of today (company working plan), used as the time range of the board.
        $working_plan = json_decode((string) setting('company_working_plan', '{}'), true) ?: [];
        $day_plan = $working_plan[$weekday_keys[(int) $now->format('w')]] ?? null;

        // Today's appointments.
        $records = $this->appointments_model->get([
            'is_unavailability' => false,
            'start_datetime >=' => $today . ' 00:00:00',
            'start_datetime <=' => $today . ' 23:59:59',
        ]);

        usort($records, fn($a, $b) => strcmp($a['start_datetime'], $b['start_datetime']));

        $services = [];
        $appointments = [];

        foreach ($records as $record) {
            $service_id = (int) $record['id_services'];

            if (!isset($services[$service_id])) {
                try {
                    $services[$service_id] = $this->services_model->find($service_id);
                } catch (Throwable) {
                    $services[$service_id] = ['name' => '-'];
                }
            }

            try {
                $customer = $this->customers_model->find((int) $record['id_users_customer']);
                $customer_name = trim(($customer['last_name'] ?? '') . ($customer['first_name'] ?? ''));
            } catch (Throwable) {
                $customer_name = '';
            }

            $start = new DateTime($record['start_datetime'], $timezone);
            $end = new DateTime($record['end_datetime'], $timezone);

            $appointments[] = [
                'id' => (int) $record['id'],
                'provider_id' => (int) $record['id_users_provider'],
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'start_minute' => (int) $start->format('G') * 60 + (int) $start->format('i'),
                'end_minute' => (int) $end->format('G') * 60 + (int) $end->format('i'),
                'service' => $services[$service_id]['name'] ?? '-',
                'status' => (string) ($record['status'] ?? ''),
                'customer' => $this->mask_name($customer_name),
            ];
        }

        // Appointment counts of the next 7 days (today included).
        $week = [];

        for ($i = 0; $i < 7; $i++) {
            $day = (clone $now)->modify('+' . $i . ' day');

            $week[] = [
                'label' => $i === 0 ? '오늘' : $day->format('n/j') . ' (' . $weekdays[(int) $day->format('w')] . ')',
                'count' => $this->db
                    ->where('is_unavailability', 0)
                    ->where('start_datetime >=', $day->format('Y-m-d') . ' 00:00:00')
                    ->where('start_datetime <=', $day->format('Y-m-d') . ' 23:59:59')
                    ->count_all_results('appointments'),
            ];
        }

        $this->load->model('appointment_change_requests_model');

        return [
            'now' => $now->format('Y-m-d H:i:s'),
            'date_text' => $now->format('Y년 n월 j일') . ' (' . $weekdays[(int) $now->format('w')] . ')',
            'company_name' => setting('company_name'),
            'day' => [
                'open' => $day_plan['start'] ?? null,
                'close' => $day_plan['end'] ?? null,
                'breaks' => $day_plan['breaks'] ?? [],
            ],
            'providers' => array_values($providers),
            'appointments' => $appointments,
            'week' => $week,
            'pending_requests' => $this->appointment_change_requests_model->count_pending(null),
            'status_styles' => (object) (json_decode((string) setting('calendar_status_styles', '{}'), true) ?: []),
        ];
    }

    /**
     * 홍길동 → 홍*동, 홍길 → 홍*, 남궁민수 → 남**수 (spaces are removed first).
     */
    private function mask_name(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '손님';
        }

        $chars = mb_str_split(preg_replace('/\s+/u', '', $name));
        $count = count($chars);

        if ($count <= 1) {
            return $name;
        }

        if ($count === 2) {
            return $chars[0] . '*';
        }

        return $chars[0] . str_repeat('*', $count - 2) . $chars[$count - 1];
    }
}
