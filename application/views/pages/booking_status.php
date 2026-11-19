<?php
/**
 * Appointment status page, shown by the customer appointment link when the "customer_change_mode" setting is
 * "view_only" or "approval".
 */
extend('layouts/message_layout');

$pending_request = vars('pending_request');
$latest_request = vars('latest_request');
?>

<?php section('content'); ?>

<div class="text-center">
    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-3"
         style="width: 80px; height: 80px;">
        <i class="fas fa-calendar-check fa-2x text-success"></i>
    </div>

    <h3 class="fw-semibold mb-3"><?= lang('appointment_status') ?></h3>

    <?php if (vars('appointment_status') !== ''): ?>
        <div class="mb-3">
            <span class="badge bg-success fs-6"><?= e(vars('appointment_status')) ?></span>
        </div>
    <?php endif; ?>

    <table class="table table-sm text-start mx-auto mb-4" style="max-width: 420px;">
        <tbody>
        <?php foreach (vars('appointment_rows') as $label => $value): ?>
            <tr>
                <th class="text-muted fw-normal text-nowrap" style="width: 80px;"><?= e($label) ?></th>
                <td><?= e($value) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($pending_request): ?>
        <div class="alert alert-warning text-start mx-auto" style="max-width: 420px;">
            <div class="fw-semibold mb-2">
                <i class="fas fa-hourglass-half me-2"></i>
                <?= $pending_request['type'] === 'cancel'
                    ? lang('cancellation_request_pending')
                    : lang('change_request_pending') ?>
            </div>

            <?php if (vars('requested_rows')): ?>
                <div class="small mb-1"><?= lang('requested_change') ?></div>
                <ul class="small mb-0 ps-3">
                    <?php foreach (vars('requested_rows') as $label => $value): ?>
                        <li><?= e($label) ?>: <?= e($value) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php elseif ($latest_request && $latest_request['status'] === 'rejected'): ?>
        <div class="alert alert-secondary mx-auto" style="max-width: 420px;">
            <i class="fas fa-info-circle me-2"></i>
            <?= lang('change_request_rejected') ?>
        </div>
    <?php endif; ?>

    <?php if (vars('can_request')): ?>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mb-2">
            <a href="<?= e(vars('edit_url')) ?>" class="btn btn-primary px-4 py-2">
                <i class="fas fa-edit me-2"></i>
                <?= lang('request_change') ?>
            </a>

            <button type="button" class="btn btn-outline-danger px-4 py-2" data-bs-toggle="collapse"
                    data-bs-target="#cancel-request-form">
                <i class="fas fa-times me-2"></i>
                <?= lang('request_cancellation') ?>
            </button>
        </div>

        <form id="cancel-request-form" class="collapse mx-auto text-start mt-3" style="max-width: 420px;"
              method="post" action="<?= e(vars('cancel_url')) ?>">
            <label for="cancellation-reason" class="form-label small"><?= lang('cancellation_reason') ?></label>
            <textarea id="cancellation-reason" name="cancellation_reason" class="form-control mb-2" rows="2"
                      maxlength="1000" required></textarea>
            <button type="submit" class="btn btn-danger w-100"><?= lang('send_cancellation_request') ?></button>
        </form>

        <p class="text-muted small mt-3 mb-0"><?= lang('change_request_hint') ?></p>
    <?php elseif (vars('change_mode') === 'view_only'): ?>
        <p class="text-muted small mb-0"><?= lang('appointment_change_not_allowed_message') ?></p>
    <?php elseif (vars('is_locked') && !$pending_request): ?>
        <p class="text-muted small mb-0"><?= lang('appointment_change_period_over') ?></p>
    <?php endif; ?>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
