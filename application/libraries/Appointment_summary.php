<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Appointment summary library.
 *
 * Builds the human-readable (Korean) summary of an appointment that is shown to customers: on the booking
 * confirmation page, in the KakaoTalk share text and on the appointment status page.
 *
 * @package Libraries
 */
class Appointment_summary
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('services_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('customers_model');
    }

    /**
     * Get the summary rows of an appointment as label => value pairs.
     *
     * @param array $appointment Appointment record (or requested values with the same keys).
     * @param bool $include_customer Whether to add the customer name row.
     *
     * @return array
     */
    public function rows(array $appointment, bool $include_customer = true): array
    {
        // A deleted service or provider must not break the page that shows the summary.
        try {
            $service = $this->CI->services_model->find((int) $appointment['id_services']);
        } catch (Throwable) {
            $service = ['name' => '-', 'duration' => '-', 'price' => 0, 'currency' => '', 'location' => ''];
        }

        try {
            $provider = $this->CI->providers_model->find((int) $appointment['id_users_provider']);
        } catch (Throwable) {
            $provider = ['last_name' => '-', 'first_name' => ''];
        }

        $rows = [
            '서비스' => $service['name'] . ' (' . $service['duration'] . '분)',
            '담당자' => trim($provider['last_name'] . ' ' . $provider['first_name']),
            '일시' => $this->format_datetime($appointment['start_datetime']),
        ];

        if ((float) $service['price'] > 0) {
            $rows['가격'] = $this->format_price($service['price'], (string) ($service['currency'] ?? ''));
        }

        if ($include_customer && !empty($appointment['id_users_customer'])) {
            try {
                $customer = $this->CI->customers_model->find((int) $appointment['id_users_customer']);
                $rows['예약자'] = trim($customer['last_name'] . ' ' . $customer['first_name']);
            } catch (Throwable) {
                $rows['예약자'] = '-';
            }
        }

        if (!empty($service['location'])) {
            $rows['장소'] = $service['location'];
        }

        return $rows;
    }

    /**
     * Get the plain text summary used for sharing via KakaoTalk.
     *
     * @param array $appointment
     *
     * @return string
     */
    public function text(array $appointment): string
    {
        $lines = ['[' . setting('company_name') . '] 예약 안내', ''];

        foreach ($this->rows($appointment) as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }

        return implode("\n", $lines);
    }

    /**
     * Format a date time as "2026년 11월 14일 (토) 14:00".
     *
     * @param string $datetime
     *
     * @return string
     */
    public function format_datetime(string $datetime): string
    {
        $weekdays = ['일', '월', '화', '수', '목', '금', '토'];

        $date = new DateTime($datetime);

        return $date->format('Y년 n월 j일') . ' (' . $weekdays[(int) $date->format('w')] . ') ' . $date->format('H:i');
    }

    /**
     * Format a price with the "display_price_decimals" and "default_currency" settings (same rules as
     * App.Utils.String.formatPrice in JS).
     *
     * @param mixed $price
     * @param string $currency Service currency; the default currency is used when empty.
     *
     * @return string
     */
    public function format_price(mixed $price, string $currency): string
    {
        $decimals = setting('display_price_decimals', '0') === '1' ? 2 : 0;

        $amount = number_format((float) $price, $decimals);

        $unit = trim($currency !== '' ? $currency : (string) setting('default_currency', '원'));

        if ($unit === '') {
            return $amount;
        }

        return preg_match('/^[\x{AC00}-\x{D7A3}]+$/u', $unit) ? $amount . $unit : $amount . ' ' . $unit;
    }
}
