<?php
/**
 * "내 예약" page (customers logged in with Kakao): upcoming appointments and visit history.
 */
extend('layouts/message_layout');
?>

<?php section('content'); ?>

<div class="text-start">
    <?php if (!vars('logged_in')): ?>
        <div class="text-center">
            <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-3"
                 style="width: 72px; height: 72px; background: #fff6bf;">
                <i class="fas fa-calendar-check fa-2x" style="color: #3c1e1e;"></i>
            </div>
            <h4 class="fw-semibold mb-2"><?= lang('my_appointments') ?></h4>
            <p class="text-muted mb-4"><?= lang('my_appointments_login_text') ?></p>
            <?php component('kakao_login_button', ['return' => 'my_appointments']); ?>
            <div class="mt-4">
                <a href="<?= site_url() ?>" class="small"><?= lang('go_to_booking_page') ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="fw-semibold mb-1"><?= lang('my_appointments') ?></h4>
                <div class="text-muted small">
                    <?= strtr(lang('my_appointments_greeting'), ['{name}' => '<strong>' . e(vars('display_name')) . '</strong>']) ?>
                </div>
            </div>
            <div class="text-end">
                <a href="<?= site_url('kakao_login/logout') ?>" class="btn btn-sm btn-link text-muted p-0"><?= lang('logout') ?></a>
                <div>
                    <a href="<?= site_url('kakao_login/switch_account?return=my_appointments') ?>"
                       class="small text-muted"><?= lang('kakao_switch_account') ?></a>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row g-2 text-center mb-4">
            <div class="col-4">
                <div class="rounded p-2" style="background: #f5f5f7;">
                    <div class="fs-4 fw-bold"><?= count(vars('upcoming')) ?></div>
                    <div class="small text-muted"><?= lang('upcoming_appointments') ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="rounded p-2" style="background: #f5f5f7;">
                    <div class="fs-4 fw-bold"><?= (int) vars('visit_count') ?></div>
                    <div class="small text-muted"><?= lang('total_visits') ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="rounded p-2 h-100 d-flex flex-column justify-content-center" style="background: #f5f5f7;">
                    <div class="fw-bold small"><?= vars('last_visit') ? e(explode(' (', vars('last_visit'))[0]) : '-' ?></div>
                    <div class="small text-muted"><?= lang('last_visit') ?></div>
                </div>
            </div>
        </div>

        <!-- Upcoming -->
        <h6 class="fw-bold mb-2"><?= lang('upcoming_appointments') ?></h6>

        <?php if (empty(vars('upcoming'))): ?>
            <div class="text-muted small border rounded p-3 mb-3 text-center"><?= lang('no_upcoming_appointments') ?></div>
        <?php endif; ?>

        <?php foreach (vars('upcoming') as $item): ?>
            <div class="border rounded p-3 mb-2 <?= $item['is_cancelled'] ? 'opacity-50' : '' ?>">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-bold"><?= e($item['date_text']) ?></div>
                    <?php if ($item['status'] !== ''): ?>
                        <span class="badge bg-success-subtle text-success-emphasis"><?= e($item['status']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="mt-1"><?= e($item['service']) ?></div>
                <div class="small text-muted">
                    <?= lang('provider') ?>: <?= e($item['provider']) ?>
                    <?php if ($item['price'] !== ''): ?> · <?= e($item['price']) ?><?php endif; ?>
                    <?php if ($item['location'] !== ''): ?> · <?= e($item['location']) ?><?php endif; ?>
                </div>

                <?php if (!empty($item['pending_request'])): ?>
                    <div class="small text-warning-emphasis mt-2">
                        <i class="fas fa-hourglass-half me-1"></i>
                        <?= $item['pending_request']['type'] === 'cancel'
                            ? lang('cancellation_request_pending')
                            : lang('change_request_pending') ?>
                    </div>
                <?php endif; ?>

                <?php if (!$item['is_cancelled']): ?>
                    <a href="<?= e($item['manage_url']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                        <?= lang('view_my_appointment') ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <a href="<?= site_url() ?>" class="btn btn-primary w-100 my-3">
            <i class="fas fa-plus me-1"></i><?= lang('book_new_appointment') ?>
        </a>

        <!-- History -->
        <h6 class="fw-bold mt-3 mb-2"><?= lang('visit_history') ?></h6>

        <?php if (empty(vars('history'))): ?>
            <div class="text-muted small border rounded p-3 text-center"><?= lang('no_visit_history') ?></div>
        <?php else: ?>
            <ul class="list-group list-group-flush border rounded">
                <?php foreach (vars('history') as $item): ?>
                    <li class="list-group-item <?= $item['is_cancelled'] ? 'text-muted' : '' ?>">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="small fw-semibold"><?= e($item['date_text']) ?></span>
                            <?php if ($item['is_cancelled']): ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e($item['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="small">
                            <?= e($item['service']) ?> · <?= e($item['provider']) ?>
                            <?php if ($item['price'] !== ''): ?> · <?= e($item['price']) ?><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
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
