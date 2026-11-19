<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div>
    <?php if (vars('message_fa_icon')): ?>
        <i id="message-icon" class="<?= e(vars('message_fa_icon')) ?> fa-4x text-success mt-0 mb-5"></i>
    <?php else: ?>
        <img id="message-icon" class="mt-0 mb-5" src="<?= vars('message_icon') ?>" alt="warning">
    <?php endif; ?>
</div>

<div class="mb-5">
    <h4 class="mb-5"><?= vars('message_title') ?></h4>

    <p><?= pure_html(vars('message_text')) ?></p>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
