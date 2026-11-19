/**
 * Calendar auto refresh.
 *
 * Reloads the calendar appointments (the same as the toolbar reload button, without reloading the page) every 30
 * seconds and when the browser tab becomes visible again, so that new bookings, changes and cancellations show up
 * without pressing refresh.
 *
 * It waits while the user is busy: an event popover or a modal (appointment editing, status change ...) is open, an
 * event is being dragged or resized, or the tab is hidden.
 */
(function () {
    const INTERVAL_MS = 30 * 1000;
    const MIN_GAP_MS = 10 * 1000; // No reload when the last one was less than this ago.

    let lastRefresh = Date.now();

    function isBusy() {
        return (
            document.hidden ||
            $('.modal.show').length > 0 ||
            $('.popover').length > 0 ||
            $('.fc-event-dragging, .fc-event-resizing, .fc-event-mirror').length > 0 ||
            $('.dropdown-menu.show').length > 0
        );
    }

    function refresh(force = false) {
        if (isBusy() || (!force && Date.now() - lastRefresh < MIN_GAP_MS)) {
            return;
        }

        const $reload = $('#reload-appointments');

        if (!$reload.length) {
            return;
        }

        lastRefresh = Date.now();

        // Silent: no full-screen loading overlay for the automatic refreshes.
        const $loading = $('#loading');
        $loading.css('visibility', 'hidden');
        $(document).one('ajaxStop', () => $loading.css('visibility', ''));

        $reload.trigger('click');
    }

    document.addEventListener('DOMContentLoaded', () => {
        setInterval(() => refresh(true), INTERVAL_MS);

        // Coming back to the tab (e.g. from another window or the phone's lock screen): refresh right away.
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refresh();
            }
        });

        window.addEventListener('focus', () => refresh());

        // Manual reloads count too.
        $(document).on('click', '#reload-appointments', () => {
            lastRefresh = Date.now();
        });
    });
})();
