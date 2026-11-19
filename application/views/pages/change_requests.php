<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
$csrf_token = get_instance()->security->get_csrf_hash();

$type_label = fn(array $request) => $request['type'] === 'cancel'
    ? '<span class="badge bg-danger">' . lang('cancellation_request') . '</span>'
    : '<span class="badge bg-primary">' . lang('change_request') . '</span>';
?>

<div id="change-requests-page" class="container backend-page py-3">
    <div class="col-lg-10 offset-lg-1">

        <h4 class="fw-light mb-3"><?= lang('change_requests') ?></h4>

        <?php if (vars('change_mode') !== 'approval'): ?>
            <div class="alert alert-info small">
                <i class="fas fa-info-circle me-2"></i>
                <?= lang('change_requests_mode_off') ?>
            </div>
        <?php endif; ?>

        <?php if (vars('flash_success')): ?>
            <div class="alert alert-success"><?= e(vars('flash_success')) ?></div>
        <?php endif; ?>

        <?php if (vars('flash_error')): ?>
            <div class="alert alert-danger"><?= e(vars('flash_error')) ?></div>
        <?php endif; ?>

        <h5 class="fw-light mt-4 mb-3"><?= lang('pending_change_requests') ?></h5>

        <?php if (empty(vars('pending_requests'))): ?>
            <p class="text-muted"><?= lang('no_pending_change_requests') ?></p>
        <?php endif; ?>

        <?php foreach (vars('pending_requests') as $request): ?>
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div>
                        <?= $type_label($request) ?>
                        <strong class="ms-2"><?= e($request['customer_name']) ?></strong>
                        <?php if ($request['customer_phone']): ?>
                            <a class="ms-2 small" href="tel:<?= e($request['customer_phone']) ?>">
                                <i class="fas fa-phone me-1"></i><?= e($request['customer_phone']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted"><?= lang('requested_at') ?> <?= e($request['created_text']) ?></small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="small text-muted mb-1"><?= lang('current_appointment') ?></div>
                            <?php foreach ($request['current_rows'] as $label => $value): ?>
                                <div><?= e($label) ?>: <?= e($value) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <?php if ($request['type'] === 'cancel'): ?>
                                <div class="small text-muted mb-1"><?= lang('cancellation_reason') ?></div>
                                <div><?= nl2br(e((string) $request['notes'])) ?></div>
                            <?php else: ?>
                                <div class="small text-muted mb-1"><?= lang('requested_change') ?></div>
                                <?php foreach ($request['requested_rows'] as $label => $value): ?>
                                    <?php $changed = ($request['current_rows'][$label] ?? null) !== $value; ?>
                                    <div class="<?= $changed ? 'fw-bold text-primary' : '' ?>">
                                        <?= e($label) ?>: <?= e($value) ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($request['notes'])): ?>
                                    <div class="small mt-1"><?= lang('notes') ?>: <?= e($request['notes']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (vars('can_edit')): ?>
                        <div class="d-flex gap-2 justify-content-end mt-2">
                            <form method="post" action="<?= site_url('change_requests/reject') ?>"
                                  onsubmit="return confirm(<?= e(json_encode(lang('reject_change_request_confirm'), JSON_UNESCAPED_UNICODE)) ?>);">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i><?= lang('reject') ?>
                                </button>
                            </form>
                            <form method="post" action="<?= site_url('change_requests/approve') ?>"
                                  onsubmit="return confirm(<?= e(json_encode(lang('approve_change_request_confirm'), JSON_UNESCAPED_UNICODE)) ?>);">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i><?= lang('approve') ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!empty(vars('handled_requests'))): ?>
            <h5 class="fw-light mt-5 mb-3"><?= lang('handled_change_requests') ?></h5>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <tbody>
                    <?php foreach (vars('handled_requests') as $request): ?>
                        <tr>
                            <td class="text-nowrap"><?= $type_label($request) ?></td>
                            <td class="text-nowrap"><?= e($request['customer_name']) ?></td>
                            <td class="small">
                                <?php // Approved cancellations are not listed (their appointment is deleted). ?>
                                <?= $request['type'] === 'cancel'
                                    ? e($request['current_rows']['일시'] ?? '')
                                    : lang('requested_change') . ': ' . e($request['requested_rows']['일시'] ?? '') ?>
                            </td>
                            <td class="text-nowrap">
                                <?= $request['status'] === 'approved'
                                    ? '<span class="text-success">' . lang('approved') . '</span>'
                                    : '<span class="text-muted">' . lang('rejected') . '</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto refresh every 60 s (and when coming back to the tab), so new requests show up without pressing F5.
    // Skipped while a confirm dialog is open (confirm() blocks timers) or the tab is hidden.
    (function () {
        let last = Date.now();
        const reload = () => {
            if (!document.hidden && Date.now() - last > 10000) {
                last = Date.now();
                window.location.reload();
            }
        };
        setInterval(reload, 60000);
        document.addEventListener('visibilitychange', reload);
    })();
</script>

<?php end_section('content'); ?>
