<?php
/**
 * Call button for customer pages (booking settings → 전화 버튼).
 *
 * One number: the button calls it directly. Several numbers: the button opens a popup with one button per number.
 *
 * Local variables.
 *
 * @var string $page Page key: booking, confirmation, status or messages.
 * @var string $variant "header": small button in the top-right corner of the booking page header (the header must be
 *                      position-relative). "inline": centered button below the content of message pages.
 */

if (setting('call_button_on_' . $page, '1') !== '1') {
    return;
}

$call_buttons = json_decode((string) setting('call_buttons', '[]'), true);

$call_buttons = array_values(
    array_filter(is_array($call_buttons) ? $call_buttons : [], fn($button) => !empty($button['phone'])),
);

if (empty($call_buttons)) {
    return;
}

$tel = fn(array $button) => 'tel:' . preg_replace('/[^0-9+]/', '', $button['phone']);
$label = fn(array $button) => $button['name'] !== '' ? $button['name'] : $button['phone'];
$single = count($call_buttons) === 1;

// Placed in the page flow (not floating), so that it never covers other buttons or the cookie notice.
$is_header = ($variant ?? 'inline') === 'header';
$button_class = $is_header
    ? 'btn btn-sm btn-light rounded-pill px-3 fw-semibold position-absolute top-0 end-0 m-2 m-md-3'
    : 'btn btn-outline-success rounded-pill px-4 py-2 fw-semibold';
?>

<?php if ($is_header): ?>
    <style>
        /* Wide screens: the header is a row (logo | steps), so put the button at the end of it, after the steps. */
        @media (min-width: 992px) {
            #header #call-button-fab {
                position: static !important;
                order: 3;
                margin: 0 0 0 1.5rem !important;
                white-space: nowrap;
            }
        }
    </style>
<?php else: ?>
    <div class="mt-3 mb-2">
<?php endif; ?>

<?php if ($single): ?>
    <a id="call-button-fab" href="<?= e($tel($call_buttons[0])) ?>" class="<?= $button_class ?>"
       style="z-index: 2;">
        <i class="fas fa-phone me-1"></i>
        <?= e($label($call_buttons[0])) ?>
    </a>
<?php else: ?>
    <button id="call-button-fab" type="button" class="<?= $button_class ?>" style="z-index: 2;"
            data-bs-toggle="modal" data-bs-target="#call-button-modal">
        <i class="fas fa-phone me-1"></i>
        <?= lang('call_us') ?>
    </button>
<?php endif; ?>

<?php if (!$is_header): ?></div><?php endif; ?>

<?php if (!$single): ?>
    <div class="modal fade" id="call-button-modal" tabindex="-1" aria-labelledby="call-button-modal-title"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="call-button-modal-title">
                        <i class="fas fa-phone me-2"></i><?= lang('call_us') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="<?= lang('close') ?>"></button>
                </div>
                <div class="modal-body d-grid gap-2">
                    <?php foreach ($call_buttons as $button): ?>
                        <a href="<?= e($tel($button)) ?>" class="btn btn-outline-success text-start py-2">
                            <div class="fw-semibold"><?= e($label($button)) ?></div>
                            <?php if ($button['name'] !== ''): ?>
                                <small><?= e($button['phone']) ?></small>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keep the popup a direct child of <body>, so that no parent box can hide it under the dark backdrop.
        document.body.appendChild(document.getElementById('call-button-modal'));
    </script>
<?php endif; ?>
