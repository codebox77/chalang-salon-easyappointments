<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
// Status badges of the Telegram and Kakao cards.
get_instance()->load->library('telegram_notifier');
$telegram_chats = count(get_instance()->telegram_notifier->chat_ids());
$telegram_has_token = setting('telegram_bot_token', '') !== '';
$telegram_on = setting('telegram_enabled') === '1' && $telegram_has_token && $telegram_chats > 0;
$kakao_key = trim((string) setting('kakao_javascript_key', '')) ?:
    (defined('Config::KAKAO_JAVASCRIPT_KEY') ? Config::KAKAO_JAVASCRIPT_KEY : '');
?>

<div id="integrations-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="integrations" class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                <?= lang('integrations') ?>
            </h4>

            <p class="form-text text-muted mb-4">
                <?= lang('integrations_info') ?>
            </p>

            <div class="row">
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0 d-flex align-items-center justify-content-between gap-2">
                                <span><i class="fas fa-comment me-1" style="color: #f9d71c;"></i><?= lang('kakao') ?></span>
                                <?php if ($kakao_key !== ''): ?>
                                    <span class="badge bg-success fw-normal"><?= lang('integration_configured') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger fw-normal"><?= lang('integration_required') ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('kakao_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('kakao_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0 d-flex align-items-center justify-content-between gap-2">
                                <span><i class="fab fa-telegram me-1"></i><?= lang('telegram') ?></span>
                                <?php if ($telegram_on): ?>
                                    <span class="badge bg-success fw-normal">
                                        <?= strtr(lang('integration_configured_chats'), ['{count}' => $telegram_chats]) ?>
                                    </span>
                                <?php elseif ($telegram_has_token || $telegram_chats > 0): ?>
                                    <span class="badge bg-warning text-dark fw-normal">
                                        <?= strtr(lang('integration_off_chats'), ['{count}' => $telegram_chats]) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fw-normal"><?= lang('integration_not_configured') ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('telegram_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('telegram_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('webhooks') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('webhooks_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('webhooks') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('google_analytics') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('google_analytics_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('google_analytics_settings') ?>"
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('matomo_analytics') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('matomo_analytics_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('matomo_analytics_settings') ?>"
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('api') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('api_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('api_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('ldap') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('ldap_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('ldap_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('google_calendar') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('google_calendar_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('google_calendar_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('caldav') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('caldav_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('caldav_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('jitsi') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('jitsi_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('jitsi_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('altcha') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('altcha_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('altcha_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

