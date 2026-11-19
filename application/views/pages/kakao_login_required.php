<?php
/**
 * Shown instead of the booking page when the customer Kakao login is "required" and nobody is logged in.
 */
extend('layouts/message_layout');
?>

<?php section('content'); ?>

<div class="text-center">
    <img src="<?= base_url('assets/img/logo.png') ?>" alt="logo" class="mb-4 rounded" style="max-height: 72px; background: #000; padding: 8px 14px;">

    <h4 class="fw-semibold mb-3"><?= lang('kakao_login_required_title') ?></h4>

    <p class="text-muted mb-4"><?= lang('kakao_login_required_text') ?></p>

    <?php component('kakao_login_button'); ?>

    <div class="mt-3">
        <a href="<?= site_url('my_appointments') ?>" class="small"><?= lang('my_appointments_link') ?></a>
    </div>

    <p class="text-muted small mt-4 mb-0"><?= lang('kakao_login_privacy_note') ?></p>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
