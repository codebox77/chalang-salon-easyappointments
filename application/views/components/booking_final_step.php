<?php
/**
 * Local variables.
 *
 * @var bool $manage_mode
 * @var string $display_terms_and_conditions
 * @var string $display_privacy_policy
 */
?>

<div id="wizard-frame-4" class="wizard-frame p-3 p-md-4" style="display:none;">
    <div class="frame-container py-3">
        <h2 class="frame-title fw-light text-center mb-3 text-muted"><?= lang('appointment_confirmation') ?></h2>

        <!-- Make clear the booking is not done yet, so customers don't close the page before confirming. -->
        <div class="alert alert-warning text-center fw-semibold m-auto mb-3" style="max-width: 630px;" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php if ($manage_mode && vars('customer_change_mode') === 'approval'): ?>
                <?= lang('change_request_notice') ?>
            <?php else: ?>
                <?= $manage_mode ? lang('booking_update_not_saved_notice') : lang('booking_not_completed_notice') ?>
            <?php endif; ?>
        </div>

        <div class="row frame-content m-auto pt-md-4 mb-4" style="max-width: 630px;">
            <div id="appointment-details" class="col-12 col-lg-6 text-center text-md-start mb-2 mb-md-0 fs-5" style="line-height: 28px;">
                <!-- JS -->
            </div>

            <div id="customer-details" class="col-12 col-lg-6 text-center text-md-end fs-5" style="line-height: 28px;">
                <!-- JS -->
            </div>

        </div>

        <?php if (setting('require_captcha')): ?>
            <?php if (setting('altcha_enabled') === '1'): ?>
                <div class="row frame-content m-auto" style="max-width: 630px;">
                    <div class="col">
                        <div id="altcha-widget" class="altcha-widget mb-2"></div>
                        <input type="hidden" id="altcha-payload" value="">
                        <span id="altcha-hint" class="help-block text-danger small" style="opacity:0">&nbsp;</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="row frame-content m-auto" style="max-width: 630px;">
                    <div class="col">
                        <label class="captcha-title float-start my-2 mb-2 me-md-4" for="captcha-text">
                            CAPTCHA
                            <button class="btn btn-link text-dark text-decoration-none py-0">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </label>
                        <img class="captcha-image float-start float-md-end mb-4 rounded" src="<?= site_url(
                            'captcha',
                        ) ?>" alt="CAPTCHA">
                        <input id="captcha-text" class="captcha-text form-control w-100 mb-4" type="text" value=""/>
                        <span id="captcha-hint" class="help-block text-danger small" style="opacity:0">&nbsp;</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <div class="d-flex fs-6 justify-content-around m-auto" style="max-width: 630px;">
        <?php if ($display_terms_and_conditions): ?>
            <div class="form-check mb-3">
                <input type="checkbox" class="required form-check-input" id="accept-to-terms-and-conditions">
                <label class="form-check-label" for="accept-to-terms-and-conditions">
                    <?= strtr(lang('read_and_agree_to_terms_and_conditions'), [
                        '{$link}' => '<a href="#" data-bs-toggle="modal" data-bs-target="#terms-and-conditions-modal">',
                        '{/$link}' => '</a>',
                    ]) ?>
                </label>
            </div>
        <?php endif; ?>

        <?php if ($display_privacy_policy): ?>
            <div class="form-check mb-3">
                <input type="checkbox" class="required form-check-input" id="accept-to-privacy-policy">
                <label class="form-check-label" for="accept-to-privacy-policy">
                    <?= strtr(lang('read_and_agree_to_privacy_policy'), [
                        '{$link}' => '<a href="#" data-bs-toggle="modal" data-bs-target="#privacy-policy-modal">',
                        '{/$link}' => '</a>',
                    ]) ?>
                </label>
            </div>
        <?php endif; ?>

    </div>

    <!-- Sticky so the confirm button stays on screen while scrolling on small screens. -->
    <div class="command-buttons text-center py-3 mx-auto d-flex justify-content-between align-items-center bg-white border-top"
         style="position: sticky; bottom: 0; z-index: 10; max-width: 630px;">
        <button type="button" id="button-back-4" class="btn button-back btn-outline-secondary" style="min-width: 100px; margin-right: 10px;"
                data-step_index="4">
            <i class="fas fa-chevron-left me-2"></i>
            <?= lang('back') ?>
        </button>
        <form id="book-appointment-form" class="flex-grow-1" method="post">
            <button id="book-appointment-submit" type="button" class="btn btn-primary btn-lg w-100 fw-semibold">
                <i class="fas fa-check-square me-2"></i>
                <?php if ($manage_mode && vars('customer_change_mode') === 'approval'): ?>
                    <?= lang('send_change_request') ?>
                <?php else: ?>
                    <?= $manage_mode ? lang('update') : lang('confirm_booking') ?>
                <?php endif; ?>
            </button>
            <input type="hidden" name="csrfToken"/>
            <input type="hidden" name="post_data"/>
        </form>
    </div>
</div>
