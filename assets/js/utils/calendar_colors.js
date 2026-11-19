/**
 * Calendar colors and status quick change (calendar page).
 *
 * - eventColors(appointment): background / border / text colors of an appointment event, from the "캘린더 색상"
 *   settings (vars('calendar_colors')): base color = provider color (provider mode) or the appointment color, then
 *   the status style may replace the color and/or lighten the background.
 * - The status in the event popover is a dropdown; picking a status saves it right away
 *   (calendar/update_appointment_status) and reloads the calendar.
 */
App.Utils.CalendarColors = (function () {
    const DEFAULT_COLOR = '#7cbae8';
    const LIGHTEN = {normal: 0, light: 0.45, lighter: 0.75};

    function settings() {
        return vars('calendar_colors') || {};
    }

    function parseHex(hex) {
        const match = /^#?([0-9a-f]{6})$/i.exec(String(hex || '').trim());

        if (!match) {
            return null;
        }

        const value = parseInt(match[1], 16);

        return [(value >> 16) & 255, (value >> 8) & 255, value & 255];
    }

    function toHex(rgb) {
        return '#' + rgb.map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('');
    }

    /**
     * Mix a color with white (amount 0 = unchanged, 1 = white).
     */
    function lighten(hex, amount) {
        const rgb = parseHex(hex);

        return rgb ? toHex(rgb.map((channel) => channel + (255 - channel) * amount)) : hex;
    }

    function textColorFor(hex) {
        const rgb = parseHex(hex);

        if (!rgb) {
            return '#ffffff';
        }

        const luminance = (0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]) / 255;

        return luminance > 0.6 ? '#212529' : '#ffffff';
    }

    /**
     * Get the FullCalendar color props of an appointment event.
     *
     * @param {Object} appointment Appointment record (with id_users_provider and status).
     *
     * @return {{backgroundColor: String, borderColor: String, textColor: String}}
     */
    function eventColors(appointment) {
        const config = settings();
        const providerColors = config.provider_colors || {};
        const statusStyle = (config.status_styles || {})[appointment.status] || {};

        let base = appointment.color || DEFAULT_COLOR;

        if (config.mode === 'provider' && parseHex(providerColors[appointment.id_users_provider])) {
            base = providerColors[appointment.id_users_provider];
        }

        if (parseHex(statusStyle.color)) {
            base = statusStyle.color;
        }

        if (!parseHex(base)) {
            base = DEFAULT_COLOR;
        }

        const background = lighten(base, LIGHTEN[statusStyle.brightness] || 0);

        return {
            backgroundColor: background,
            borderColor: base,
            textColor: textColorFor(background),
        };
    }

    /**
     * Create the status element of the event popover: a link that opens the status popup when the user may edit.
     *
     * @param {Object} appointment
     *
     * @return {jQuery}
     */
    function renderStatus(appointment) {
        const status = appointment.status || '';
        const options = vars('appointment_status_options') || [];

        if (!vars('privileges').appointments.edit || !options.length) {
            return $('<span/>', {text: status || '-'});
        }

        // Dropdown in the event popover: picking a status saves it right away.
        const $select = $('<select/>', {
            'class': 'form-select form-select-sm d-inline-block w-auto py-0 status-quick-select',
            'data-appointment-id': appointment.id,
            'data-previous': status,
            'data-provider': appointment.id_users_provider || '',
            'data-color': appointment.color || '',
            'aria-label': lang('change_status'),
        });

        // Keep a status that is not (or no longer) in the list, so that it is shown as it is.
        if (!options.includes(status)) {
            $select.append(new Option(status || '-', status, true, true));
        }

        options.forEach((option) => $select.append(new Option(option, option, false, option === status)));

        applySelectColors($select, appointment);

        return $select;
    }

    /**
     * Color the dropdown like the event of the selected status.
     */
    function applySelectColors($select, appointment) {
        const colors = eventColors({...appointment, status: $select.val()});

        $select.css({
            'background-color': colors.backgroundColor,
            'color': colors.textColor,
            'border-color': colors.borderColor,
            'font-weight': 600,
        });
    }

    /**
     * Provider and color of the appointment a dropdown belongs to (for its colors).
     */
    function savedAppointment($select) {
        return {id_users_provider: $select.data('provider'), color: String($select.data('color') || '')};
    }

    function saveStatus($select) {
        const appointmentId = $select.data('appointment-id');
        const status = $select.val();
        const previous = String($select.data('previous') ?? '');

        if (status === previous) {
            return;
        }

        $select.prop('disabled', true);

        $.post(App.Utils.Url.siteUrl('calendar/update_appointment_status'), {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            status,
        })
            .done(() => {
                $select.data('previous', status);

                // Close the event popover through the calendar's own close button (disposes it properly).
                const $close = $('.popover .close-popover');

                if ($close.length) {
                    $close.first().trigger('click');
                } else {
                    $('.popover').remove();
                }

                $('#reload-appointments').trigger('click');

                App.Layouts.Backend.displayNotification(lang('status_changed') + ': ' + status);
            })
            .fail(() => {
                // Back to the saved status (the error itself is shown by the global AJAX error handler).
                $select.val(previous).prop('disabled', false);
                applySelectColors($select, savedAppointment($select));
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        $(document).on('change', '.status-quick-select', (event) => {
            const $select = $(event.currentTarget);

            applySelectColors($select, savedAppointment($select));

            saveStatus($select);
        });
    });

    return {
        eventColors,
        renderStatus,
        lighten,
    };
})();
