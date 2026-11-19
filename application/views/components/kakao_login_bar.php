<?php
/**
 * Kakao login bar at the top of the booking page (customer Kakao login "optional" or "required").
 * Logged in: who is logged in and a logout link. Not logged in ("optional"): the login button.
 */

$kakao_login = vars('kakao_login') ?? ['mode' => 'off', 'user' => null];

if (($kakao_login['mode'] ?? 'off') === 'off') {
    return;
}

$user = $kakao_login['user'] ?? null;
$display_name = $user ? ($user['name'] ?: ($user['nickname'] ?: lang('kakao_user'))) : '';
?>

<div id="kakao-login-bar" class="booking-header-bar d-flex flex-wrap gap-2 justify-content-between align-items-center py-2 px-3 m-0"
     style="background: #fffbe0;">
    <?php if ($user): ?>
        <small>
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-1"
                  style="width: 20px; height: 20px; background: #FEE500;">
                <i class="fas fa-comment" style="font-size: 10px; color: #3c1e1e;"></i>
            </span>
            <?= strtr(lang('kakao_logged_in_as'), ['{name}' => '<strong>' . e($display_name) . '</strong>']) ?>
        </small>
        <span class="d-flex align-items-center gap-3">
            <a href="<?= site_url('my_appointments') ?>" class="btn btn-sm fw-semibold px-3"
               style="background: #FEE500; color: rgba(0, 0, 0, 0.85); border-radius: 10px;">
                <i class="fas fa-list me-1"></i><?= lang('my_appointments') ?>
            </a>
            <a href="<?= site_url('kakao_login/switch_account') ?>" class="btn btn-sm btn-link text-muted p-0">
                <?= lang('kakao_switch_account') ?>
            </a>
            <a href="<?= site_url('kakao_login/logout') ?>" class="btn btn-sm btn-link text-muted p-0">
                <?= lang('logout') ?>
            </a>
        </span>
    <?php else: ?>
        <small><?= lang('kakao_login_optional_text') ?></small>
        <span class="d-flex align-items-center gap-3">
            <a href="<?= site_url('my_appointments') ?>" class="small text-muted"><?= lang('my_appointments') ?></a>
            <?php component('kakao_login_button'); ?>
        </span>
    <?php endif; ?>
</div>
