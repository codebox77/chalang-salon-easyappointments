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
 * Strings utility.
 *
 * This module implements the functionality of strings.
 */
window.App.Utils.String = (function () {
    /**
     * Upper case the first letter of the provided string.
     *
     * Old Name: GeneralFunctions.upperCaseFirstLetter
     *
     * @param {String} value
     *
     * @returns {string}
     */
    function upperCaseFirstLetter(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    /**
     * Escape HTML content with the use of jQuery.
     *
     * Old Name: GeneralFunctions.escapeHtml
     *
     * @param {String} content
     *
     * @return {String}
     */
    function escapeHtml(content) {
        return $('<div/>').text(content).html();
    }

    /**
     * Format a price with the "display_price_decimals" and "default_currency" settings.
     *
     * The service currency is used when set, otherwise the default currency. A Hangul-only currency (e.g. "원") is
     * attached without a space ("30,000원"), others with a space ("30.00 USD"). The result is HTML-escaped.
     *
     * @param {Number|String} price
     * @param {String} [currency]
     *
     * @return {String}
     */
    function formatPrice(price, currency) {
        const decimals = Boolean(Number(vars('display_price_decimals') ?? 0)) ? 2 : 0;

        const amount = Number(price || 0).toLocaleString('ko-KR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });

        const unit = String(currency || vars('default_currency') || '').trim();

        if (!unit) {
            return amount;
        }

        return escapeHtml(/^[가-힣]+$/.test(unit) ? amount + unit : amount + ' ' + unit);
    }

    return {
        upperCaseFirstLetter,
        escapeHtml,
        formatPrice,
    };
})();
