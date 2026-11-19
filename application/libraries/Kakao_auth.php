<?php defined('BASEPATH') or exit('No direct script access allowed');

use GuzzleHttp\Client;

/**
 * Kakao auth library (customer Kakao login on the booking page).
 *
 * OAuth 2.0 with the Kakao REST API: authorize → callback with a code → token → user profile. The logged-in Kakao
 * user is kept in the session ("kakao_user") and is linked to the customer records they book as (only safe links,
 * see may_link_customer(); table kakao_customer_links, created on first use), which "내 예약" (My_appointments) uses.
 *
 * Settings (Integrations → 카카오): kakao_login_mode (off / optional / required), kakao_rest_api_key,
 * kakao_client_secret (only when "Client Secret" is enabled in the Kakao app).
 *
 * @package Libraries
 */
class Kakao_auth
{
    private const AUTHORIZE_URL = 'https://kauth.kakao.com/oauth/authorize';
    private const TOKEN_URL = 'https://kauth.kakao.com/oauth/token';
    private const PROFILE_URL = 'https://kapi.kakao.com/v2/user/me';
    // Kakao user ↔ customer records they booked as (several when they used different names or phone numbers).
    private const LINK_TABLE = 'kakao_customer_links';
    private const OLD_LINK_TABLE = 'kakao_customers'; // First version (one record per Kakao user), copied over.

    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * "off", "optional" or "required" ("off" when the REST API key is missing).
     */
    public function mode(): string
    {
        $mode = setting('kakao_login_mode', 'off');

        if (!in_array($mode, ['optional', 'required'], true) || $this->rest_api_key() === '') {
            return 'off';
        }

        return $mode;
    }

    public function rest_api_key(): string
    {
        return trim((string) setting('kakao_rest_api_key', ''));
    }

    public function redirect_uri(): string
    {
        return trusted_site_url('kakao_login/callback');
    }

    /**
     * The Kakao login page address (with a fresh "state" kept in the session against CSRF).
     *
     * @param bool $force_login Ask for the Kakao ID and password again (prompt=login) instead of reusing the Kakao
     *                          account the browser is logged in to, so that another account can be used.
     */
    public function authorize_url(bool $force_login = false): string
    {
        $state = bin2hex(random_bytes(16));

        session(['kakao_oauth_state' => $state]);

        return self::AUTHORIZE_URL .
            '?' .
            http_build_query([
                'client_id' => $this->rest_api_key(),
                'redirect_uri' => $this->redirect_uri(),
                'response_type' => 'code',
                'state' => $state,
            ] + ($force_login ? ['prompt' => 'login'] : []));
    }

    /**
     * Check the state and turn the code into the Kakao user (id, nickname, name, email, phone).
     *
     * @throws RuntimeException
     */
    public function handle_callback(string $code, string $state): array
    {
        $expected = (string) session('kakao_oauth_state');

        $this->CI->session->unset_userdata('kakao_oauth_state');

        if ($expected === '' || !hash_equals($expected, $state)) {
            throw new RuntimeException('Invalid login state.');
        }

        $client = new Client(['timeout' => 8, 'connect_timeout' => 4, 'http_errors' => false]);

        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->rest_api_key(),
            'redirect_uri' => $this->redirect_uri(),
            'code' => $code,
        ];

        $secret = (string) setting('kakao_client_secret', '');

        if ($secret !== '') {
            $params['client_secret'] = $secret;
        }

        $token = json_decode((string) $client->post(self::TOKEN_URL, ['form_params' => $params])->getBody(), true);

        if (empty($token['access_token'])) {
            throw new RuntimeException('Kakao token error: ' . ($token['error_description'] ?? ($token['error'] ?? 'unknown')));
        }

        $profile = json_decode(
            (string) $client
                ->get(self::PROFILE_URL, ['headers' => ['Authorization' => 'Bearer ' . $token['access_token']]])
                ->getBody(),
            true,
        );

        if (empty($profile['id'])) {
            throw new RuntimeException('Kakao profile error: ' . ($profile['msg'] ?? 'unknown'));
        }

        $account = $profile['kakao_account'] ?? [];

        // Profile photo (consent item "프로필 사진"); Kakao's default silhouette is left out.
        $has_photo = empty($account['profile']['is_default_image']);

        return [
            'id' => (string) $profile['id'],
            'profile_image' => $has_photo ? $this->safe_image_url($account['profile']['profile_image_url'] ?? '') : '',
            'thumbnail_image' => $has_photo
                ? $this->safe_image_url($account['profile']['thumbnail_image_url'] ?? '')
                : '',
            'nickname' => (string) ($account['profile']['nickname'] ?? ($profile['properties']['nickname'] ?? '')),
            'name' => (string) ($account['name'] ?? ''),
            // Only an email Kakao verified (used to link existing customer records, see may_link_customer()).
            'email' => !empty($account['is_email_verified']) && !empty($account['is_email_valid'])
                ? (string) ($account['email'] ?? '')
                : '',
            'phone' => $this->normalize_phone((string) ($account['phone_number'] ?? '')),
        ];
    }

    /**
     * The Kakao user of this browser session, or null.
     */
    public function current_user(): ?array
    {
        $user = session('kakao_user');

        return is_array($user) && !empty($user['id']) ? $user : null;
    }

    public function login(array $user): void
    {
        // New session ID on login (no session fixation).
        $this->CI->session->sess_regenerate(true);

        session(['kakao_user' => $user]);

        $this->CI->session->unset_userdata('kakao_force_login');
    }

    /**
     * Log out of this site. The next Kakao login asks for the Kakao ID again (the browser may still be logged in to
     * Kakao, which would otherwise log the same account back in without asking).
     */
    public function logout(): void
    {
        $this->CI->session->unset_userdata('kakao_user');

        session(['kakao_force_login' => true]);
    }

    /**
     * Whether the next Kakao login must ask for the Kakao ID again (after a logout).
     */
    public function must_force_login(): bool
    {
        return (bool) session('kakao_force_login');
    }

    /**
     * Remember which customer record a Kakao user booked as.
     */
    public function link_customer(string $kakao_id, int $customer_id): void
    {
        $this->ensure_table();

        $key = ['kakao_id' => $kakao_id, 'id_users_customer' => $customer_id];

        if ($this->CI->db->get_where(self::LINK_TABLE, $key)->num_rows()) {
            $this->CI->db->update(self::LINK_TABLE, ['update_datetime' => date('Y-m-d H:i:s')], $key);
        } else {
            $this->CI->db->insert(self::LINK_TABLE, $key + ['update_datetime' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * The customer record a Kakao user booked as before, or null.
     */
    public function linked_customer_id(string $kakao_id): ?int
    {
        $this->ensure_table();

        $row = $this->CI->db
            ->order_by('update_datetime', 'DESC')
            ->get_where(self::LINK_TABLE, ['kakao_id' => $kakao_id], 1)
            ->row_array();

        return $row ? (int) $row['id_users_customer'] : null;
    }

    /**
     * Every customer record of a Kakao user: the ones linked to the Kakao account (see may_link_customer()).
     *
     * There is no matching by phone number: the public booking form can change the phone number of any customer
     * (customers are found by the email typed into the form), so a phone match could expose another customer.
     *
     * @return int[]
     */
    public function customer_ids(array $user): array
    {
        $this->ensure_table();

        $ids = array_map(
            'intval',
            array_column(
                $this->CI->db->get_where(self::LINK_TABLE, ['kakao_id' => (string) $user['id']])->result_array(),
                'id_users_customer',
            ),
        );

        return array_values(array_unique($ids));
    }

    /**
     * Whether a booking's customer record may be linked to the logged-in Kakao user.
     *
     * Allowed: a record created by this booking ($customer_before null), a record already linked to this Kakao
     * account, or a record whose email is the email Kakao verified for this account. Not allowed: any other existing
     * record, because the booking form finds existing customers by an email that anyone can type.
     *
     * @param array $user Kakao user (session).
     * @param array|null $customer_before The existing customer record before the booking saved it, or null when the
     *                                    booking created a new record.
     */
    public function may_link_customer(array $user, ?array $customer_before): bool
    {
        if (empty($customer_before)) {
            return true;
        }

        if (in_array((int) $customer_before['id'], $this->customer_ids($user), true)) {
            return true;
        }

        $verified_email = strtolower(trim((string) ($user['email'] ?? '')));

        return $verified_email !== '' && $verified_email === strtolower(trim((string) ($customer_before['email'] ?? '')));
    }

    /**
     * Store the Kakao user's profile photo on all their customer links (refreshed on every login, removed when they
     * no longer share one).
     */
    public function save_profile_image(array $user): void
    {
        $this->ensure_table();

        $this->CI->db->update(
            self::LINK_TABLE,
            [
                'profile_image' => (string) ($user['profile_image'] ?? ''),
                'thumbnail_image' => (string) ($user['thumbnail_image'] ?? ''),
            ],
            ['kakao_id' => (string) $user['id']],
        );
    }

    /**
     * Kakao profile photos of customers (admin customers page).
     *
     * @param int[] $customer_ids
     *
     * @return array customer id => ['photo' => url, 'thumbnail' => url]
     */
    public function photos_for_customers(array $customer_ids): array
    {
        if (empty($customer_ids)) {
            return [];
        }

        $this->ensure_table();

        $rows = $this->CI->db
            ->select('id_users_customer, profile_image, thumbnail_image')
            ->where_in('id_users_customer', array_map('intval', $customer_ids))
            ->where('profile_image !=', '')
            ->order_by('update_datetime', 'ASC') // The latest login wins below.
            ->get(self::LINK_TABLE)
            ->result_array();

        $photos = [];

        foreach ($rows as $row) {
            $photos[(int) $row['id_users_customer']] = [
                'photo' => $row['profile_image'],
                'thumbnail' => $row['thumbnail_image'] ?: $row['profile_image'],
            ];
        }

        return $photos;
    }

    /**
     * Only https photo addresses on Kakao's image servers (http addresses are upgraded).
     */
    private function safe_image_url(string $url): string
    {
        $url = preg_replace('#^http://#i', 'https://', trim($url));
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (!str_starts_with($url, 'https://') || !preg_match('/(^|\.)(kakaocdn\.net|kakao\.com)$/i', $host)) {
            return '';
        }

        return $url;
    }

    /**
     * +82 10-1234-5678 → 010-1234-5678.
     */
    private function normalize_phone(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+82')) {
            $phone = '0' . ltrim(substr($phone, 3));
        }

        return $phone;
    }

    /**
     * Created on first use instead of a migration (see Appointment_change_requests_model).
     */
    private function ensure_table(): void
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $checked = true;

        $table = $this->CI->db->dbprefix(self::LINK_TABLE);

        if ($this->CI->db->table_exists(self::LINK_TABLE)) {
            // Columns added later (profile photo).
            if (!$this->CI->db->field_exists('profile_image', self::LINK_TABLE)) {
                $this->CI->db->query(
                    "ALTER TABLE `$table` ADD `profile_image` VARCHAR(512) NOT NULL DEFAULT '', " .
                        "ADD `thumbnail_image` VARCHAR(512) NOT NULL DEFAULT ''",
                );
            }

            return;
        }

        $this->CI->db->query(
            "CREATE TABLE IF NOT EXISTS `$table` (
                `kakao_id` VARCHAR(32) NOT NULL,
                `id_users_customer` INT(11) NOT NULL,
                `update_datetime` DATETIME NULL,
                `profile_image` VARCHAR(512) NOT NULL DEFAULT '',
                `thumbnail_image` VARCHAR(512) NOT NULL DEFAULT '',
                PRIMARY KEY (`kakao_id`, `id_users_customer`),
                KEY `id_users_customer` (`id_users_customer`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        );

        if ($this->CI->db->table_exists(self::OLD_LINK_TABLE)) {
            $old = $this->CI->db->dbprefix(self::OLD_LINK_TABLE);

            $this->CI->db->query(
                "INSERT IGNORE INTO `$table` (kakao_id, id_users_customer, update_datetime) " .
                    "SELECT kakao_id, id_users_customer, update_datetime FROM `$old`",
            );
        }
    }
}
