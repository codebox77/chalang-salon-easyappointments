<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Appointment change requests model.
 *
 * Stores the reschedule and cancellation requests that customers make through their appointment link when the
 * "customer_change_mode" setting is "approval". A provider or an admin then approves or rejects each request.
 *
 * The table is created on first use (instead of a migration), so that the live site does not go into the
 * "Database Migration Required" state when this file is deployed.
 *
 * @package Models
 */
class Appointment_change_requests_model extends CI_Model
{
    public const TYPE_RESCHEDULE = 'reschedule';
    public const TYPE_CANCEL = 'cancel';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REPLACED = 'replaced'; // A newer request was made for the same appointment.

    // Values of the "customer_change_mode" setting.
    public const MODE_SELF = 'self'; // Customers change or cancel the appointment themselves (original behavior).
    public const MODE_VIEW_ONLY = 'view_only'; // The appointment link only shows the appointment status.
    public const MODE_APPROVAL = 'approval'; // Changes and cancellations wait for a provider or admin to approve.

    private const TABLE = 'appointment_change_requests';

    /**
     * Get the "customer_change_mode" setting ("self" when not set or invalid).
     */
    public static function mode(): string
    {
        $mode = setting('customer_change_mode', self::MODE_SELF);

        return in_array($mode, [self::MODE_SELF, self::MODE_VIEW_ONLY, self::MODE_APPROVAL], true)
            ? $mode
            : self::MODE_SELF;
    }

    private static bool $table_checked = false;

    public function __construct()
    {
        $this->ensure_table();
    }

    /**
     * Create the table if it does not exist yet.
     */
    private function ensure_table(): void
    {
        if (self::$table_checked) {
            return;
        }

        if (!$this->db->table_exists(self::TABLE)) {
            $table = $this->db->dbprefix(self::TABLE);

            $this->db->query(
                "CREATE TABLE IF NOT EXISTS `$table` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `id_appointments` INT(11) NOT NULL,
                    `type` VARCHAR(16) NOT NULL,
                    `start_datetime` DATETIME NULL,
                    `end_datetime` DATETIME NULL,
                    `id_services` INT(11) NULL,
                    `id_users_provider` INT(11) NULL,
                    `notes` TEXT NULL,
                    `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
                    `create_datetime` DATETIME NOT NULL,
                    `update_datetime` DATETIME NULL,
                    `id_users_handled` INT(11) NULL,
                    PRIMARY KEY (`id`),
                    KEY `id_appointments` (`id_appointments`),
                    KEY `status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            );
        }

        self::$table_checked = true;
    }

    /**
     * Create a new pending request. An older pending request of the same appointment is marked as replaced.
     *
     * @param array $request Must contain id_appointments and type; reschedule requests also the new values.
     *
     * @return int The new request ID.
     */
    public function create(array $request): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->update(
            self::TABLE,
            ['status' => self::STATUS_REPLACED, 'update_datetime' => $now],
            ['id_appointments' => $request['id_appointments'], 'status' => self::STATUS_PENDING],
        );

        $this->db->insert(self::TABLE, [
            'id_appointments' => (int) $request['id_appointments'],
            'type' => $request['type'],
            'start_datetime' => $request['start_datetime'] ?? null,
            'end_datetime' => $request['end_datetime'] ?? null,
            'id_services' => $request['id_services'] ?? null,
            'id_users_provider' => $request['id_users_provider'] ?? null,
            'notes' => $request['notes'] ?? null,
            'status' => self::STATUS_PENDING,
            'create_datetime' => $now,
        ]);

        return (int) $this->db->insert_id();
    }

    /**
     * Find a request by ID.
     */
    public function find(int $request_id): ?array
    {
        return $this->db->get_where(self::TABLE, ['id' => $request_id])->row_array() ?: null;
    }

    /**
     * Get the pending request of an appointment, if any.
     */
    public function find_pending(int $appointment_id): ?array
    {
        return $this->db
            ->order_by('id', 'DESC')
            ->get_where(self::TABLE, ['id_appointments' => $appointment_id, 'status' => self::STATUS_PENDING], 1)
            ->row_array() ?:
            null;
    }

    /**
     * Get the latest request of an appointment (any status except replaced), if any.
     */
    public function find_latest(int $appointment_id): ?array
    {
        return $this->db
            ->where('status !=', self::STATUS_REPLACED)
            ->order_by('id', 'DESC')
            ->get_where(self::TABLE, ['id_appointments' => $appointment_id], 1)
            ->row_array() ?:
            null;
    }

    /**
     * Get requests joined with their appointment, for the backend list.
     *
     * Requests of deleted appointments are left out.
     *
     * @param string[] $statuses
     * @param int[]|null $provider_ids Only requests whose appointment belongs to these providers (null = all).
     * @param int $limit
     *
     * @return array
     */
    public function get_list(array $statuses, ?array $provider_ids, int $limit = 100): array
    {
        if ($provider_ids !== null && empty($provider_ids)) {
            return [];
        }

        $this->db
            ->select(
                'r.*, a.hash AS appointment_hash, a.start_datetime AS current_start_datetime, ' .
                    'a.id_services AS current_id_services, a.id_users_provider AS current_id_users_provider, ' .
                    'a.id_users_customer AS id_users_customer',
            )
            ->from(self::TABLE . ' r')
            ->join('appointments a', 'a.id = r.id_appointments', 'inner')
            ->where_in('r.status', $statuses)
            ->order_by('r.id', 'DESC')
            ->limit($limit);

        if ($provider_ids !== null) {
            $this->db->where_in('a.id_users_provider', $provider_ids);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Count the pending requests visible to the given providers (null = all).
     */
    public function count_pending(?array $provider_ids): int
    {
        if ($provider_ids !== null && empty($provider_ids)) {
            return 0;
        }

        $this->db
            ->from(self::TABLE . ' r')
            ->join('appointments a', 'a.id = r.id_appointments', 'inner')
            ->where('r.status', self::STATUS_PENDING);

        if ($provider_ids !== null) {
            $this->db->where_in('a.id_users_provider', $provider_ids);
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Mark a request as handled.
     */
    public function set_status(int $request_id, string $status, ?int $user_id): void
    {
        $this->db->update(
            self::TABLE,
            ['status' => $status, 'update_datetime' => date('Y-m-d H:i:s'), 'id_users_handled' => $user_id],
            ['id' => $request_id],
        );
    }

    /**
     * Whether the change period of an appointment is over (setting "book_advance_timeout", in minutes before the
     * start, in the provider's timezone). Checked by the server for changes and cancellations made through the
     * appointment link, not only hidden in the page.
     */
    public function is_locked(array $appointment): bool
    {
        $this->load->model('providers_model');

        try {
            $provider = $this->providers_model->find((int) $appointment['id_users_provider']);
            $timezone = new DateTimeZone($provider['timezone'] ?: 'Asia/Seoul');
        } catch (Throwable) {
            $timezone = new DateTimeZone('Asia/Seoul');
        }

        $start = new DateTime($appointment['start_datetime'], $timezone);
        $limit = new DateTime('now', $timezone);
        $limit->modify('+' . (int) setting('book_advance_timeout', 0) . ' minutes');

        return $start < $limit;
    }

    /**
     * Get the provider IDs whose requests the logged in user may see and handle (null = all).
     *
     * @return int[]|null
     */
    public function visible_provider_ids(): ?array
    {
        $role_slug = session('role_slug');
        $user_id = (int) session('user_id');

        if ($role_slug === DB_SLUG_PROVIDER) {
            return [$user_id];
        }

        if ($role_slug === DB_SLUG_SECRETARY) {
            $this->load->model('secretaries_model');

            return array_map('intval', $this->secretaries_model->find($user_id)['providers'] ?? []);
        }

        return null; // Admins see everything.
    }
}
