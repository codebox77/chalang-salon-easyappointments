/**
 * Call buttons editor on the booking settings page.
 *
 * Renders one row (display name + phone number) per call button and keeps the hidden "call_buttons" field in sync
 * as JSON, so that the regular booking settings save picks it up. The "all pages" switch toggles the page checkboxes.
 */
(function () {
    const MAX_BUTTONS = 10;

    const $list = $('#call-buttons-list');
    const $hidden = $('#call-buttons');
    const $add = $('#add-call-button');
    const $allPages = $('#call-button-all-pages');

    function addRow(button = {name: '', phone: ''}) {
        const $row = $(`
            <div class="call-button-row row g-2 align-items-center mb-2">
                <div class="col-5">
                    <input type="text" class="form-control call-button-name" maxlength="50">
                </div>
                <div class="col-5">
                    <input type="tel" class="form-control call-button-phone" maxlength="30">
                </div>
                <div class="col-2 text-end">
                    <button type="button" class="btn btn-outline-danger remove-call-button">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `);

        $row.find('.call-button-name').val(button.name).attr('placeholder', lang('call_button_name_placeholder'));
        $row.find('.call-button-phone').val(button.phone).attr('placeholder', '010-1234-5678');
        $row.find('.remove-call-button').attr('title', lang('delete'));

        $list.append($row);

        updateAddButton();
    }

    function sync() {
        const buttons = [];

        $list.find('.call-button-row').each((index, row) => {
            const name = $(row).find('.call-button-name').val().trim();
            const phone = $(row).find('.call-button-phone').val().trim();

            if (phone) {
                buttons.push({name, phone});
            }
        });

        $hidden.val(JSON.stringify(buttons));
    }

    function updateAddButton() {
        $add.prop('disabled', $list.find('.call-button-row').length >= MAX_BUTTONS);
    }

    function updateAllPages() {
        const $pages = $('.call-button-page');
        $allPages.prop('checked', $pages.length === $pages.filter(':checked').length);
    }

    function load() {
        const setting = (vars('booking_settings') || []).find((item) => item.name === 'call_buttons');

        let buttons = [];

        try {
            buttons = JSON.parse(setting?.value || '[]');
        } catch (error) {
            buttons = [];
        }

        $list.empty();

        (Array.isArray(buttons) ? buttons : []).forEach((button) => addRow(button));

        sync();
        updateAllPages();
    }

    function initialize() {
        $add.on('click', () => {
            addRow();
            $list.find('.call-button-name').last().trigger('focus');
        });

        $list.on('input', 'input', sync);

        $list.on('click', '.remove-call-button', (event) => {
            $(event.currentTarget).closest('.call-button-row').remove();
            sync();
            updateAddButton();
        });

        $allPages.on('change', () => $('.call-button-page').prop('checked', $allPages.prop('checked')));

        $(document).on('change', '.call-button-page', updateAllPages);

        // Run after booking_settings.js has filled the checkboxes from the saved settings.
        setTimeout(load);
    }

    document.addEventListener('DOMContentLoaded', initialize);
})();
