<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
$csrf_token = get_instance()->security->get_csrf_hash();
$has_key = vars('kakao_key') !== '';
?>

<div id="kakao-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="text-black-50 mb-3 fw-light d-flex align-items-center gap-2">
                <i class="fas fa-comment" style="color: #f9d71c;"></i><?= lang('kakao') ?>
                <?php if ($has_key): ?>
                    <span class="badge bg-success fs-6 fw-normal"><?= lang('integration_configured') ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary fs-6 fw-normal"><?= lang('integration_not_configured') ?></span>
                <?php endif; ?>
            </h4>

            <?php if (vars('flash_success')): ?>
                <div class="alert alert-success"><?= e(vars('flash_success')) ?></div>
            <?php endif; ?>

            <?php if (vars('flash_error')): ?>
                <div class="alert alert-danger"><?= e(vars('flash_error')) ?></div>
            <?php endif; ?>

            <p class="text-muted"><?= lang('kakao_info') ?></p>

            <div class="card mb-4">
                <div class="card-header fw-semibold"><?= lang('kakao_required_setup') ?></div>
                <div class="card-body">
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">
                            <a href="https://developers.kakao.com/console/app" target="_blank" rel="noopener">Kakao Developers</a>
                            <?= lang('kakao_step_1') ?>
                        </li>
                        <li class="mb-2">
                            <?= lang('kakao_step_2') ?>
                            <div class="input-group input-group-sm mt-1" style="max-width: 360px;">
                                <input type="text" class="form-control" readonly id="kakao-site-domain"
                                       value="<?= e(vars('site_domain')) ?>">
                                <button class="btn btn-outline-secondary" type="button" id="copy-site-domain">
                                    <?= lang('copy') ?>
                                </button>
                            </div>
                        </li>
                        <li class="mb-2"><?= lang('kakao_step_3') ?></li>
                        <li><?= lang('kakao_step_4') ?></li>
                    </ol>
                </div>
            </div>

            <form method="post" action="<?= site_url('kakao_settings/save') ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <div class="mb-3">
                    <label class="form-label" for="kakao-javascript-key"><?= lang('kakao_javascript_key') ?></label>
                    <input type="text" class="form-control font-monospace" id="kakao-javascript-key"
                           name="kakao_javascript_key" maxlength="32" autocomplete="off" data-lpignore="true" data-1p-ignore
                           value="<?= e(vars('kakao_key')) ?>" placeholder="0123456789abcdef0123456789abcdef">
                    <div class="form-text">
                        <?= lang('kakao_javascript_key_hint') ?>
                        <?php if (vars('kakao_key_from_config')): ?>
                            <br><?= lang('kakao_key_from_config') ?>
                        <?php endif; ?>
                    </div>
                </div>

                <h5 class="fw-light mt-5 mb-2"><?= lang('kakao_customer_login') ?></h5>

                <div class="border rounded p-3 mb-3">
                    <div class="mb-3">
                        <label class="form-label" for="kakao-login-mode"><?= lang('kakao_login_mode') ?></label>
                        <select class="form-select" id="kakao-login-mode" name="kakao_login_mode">
                            <?php foreach (['off', 'optional', 'required'] as $mode): ?>
                                <option value="<?= $mode ?>" <?= vars('login_mode') === $mode ? 'selected' : '' ?>>
                                    <?= lang('kakao_login_mode_' . $mode) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= lang('kakao_login_mode_hint') ?></div>
                    </div>

                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body small">
                            <div class="fw-semibold mb-2"><?= lang('kakao_login_setup') ?></div>
                            <ol class="mb-0 ps-3">
                                <li class="mb-1"><?= lang('kakao_login_step_1') ?></li>
                                <li class="mb-1">
                                    <?= lang('kakao_login_step_2') ?>
                                    <div class="input-group input-group-sm mt-1">
                                        <input type="text" class="form-control" readonly id="kakao-redirect-uri"
                                               value="<?= e(vars('redirect_uri')) ?>">
                                        <button class="btn btn-outline-secondary" type="button" id="copy-redirect-uri">
                                            <?= lang('copy') ?>
                                        </button>
                                    </div>
                                </li>
                                <li class="mb-1"><?= lang('kakao_login_step_3') ?></li>
                                <li><?= lang('kakao_login_step_4') ?></li>
                            </ol>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="kakao-rest-api-key"><?= lang('kakao_rest_api_key') ?></label>
                        <input type="text" class="form-control font-monospace" id="kakao-rest-api-key"
                               name="kakao_rest_api_key" maxlength="32" autocomplete="off" data-lpignore="true" data-1p-ignore
                               value="<?= e(vars('rest_api_key')) ?>">
                    </div>

                    <div>
                        <label class="form-label" for="kakao-client-secret"><?= lang('kakao_client_secret') ?></label>
                        <input type="password" class="form-control font-monospace" id="kakao-client-secret"
                               name="kakao_client_secret" autocomplete="new-password" data-lpignore="true" data-1p-ignore
                               <?php // "new-password": browsers must not fill the saved admin login password in here. ?>
                               placeholder="<?= vars('has_client_secret') ? e(lang('telegram_token_saved')) : '' ?>">
                        <div class="form-text"><?= lang('kakao_client_secret_hint') ?></div>
                        <?php if (vars('has_client_secret')): ?>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="remove-client-secret"
                                       name="remove_client_secret" value="1">
                                <label class="form-check-label small" for="remove-client-secret">
                                    <?= lang('kakao_remove_client_secret') ?>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i><?= lang('save') ?>
                    </button>

                    <?php if ($has_key): ?>
                        <button type="button" class="btn btn-outline-secondary" id="kakao-test">
                            <i class="fas fa-comment me-2"></i><?= lang('kakao_test_share') ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div id="kakao-test-result" class="small mt-2"></div>
            </form>
        </div>
    </div>
</div>

<?php if ($has_key): ?>
    <script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js" crossorigin="anonymous"></script>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        [['copy-site-domain', 'kakao-site-domain'], ['copy-redirect-uri', 'kakao-redirect-uri']].forEach(([button, field]) => {
            document.getElementById(button)?.addEventListener('click', (event) => {
                const input = document.getElementById(field);
                input.select();
                navigator.clipboard?.writeText(input.value);
                event.currentTarget.textContent = '✓';
            });
        });

        // Test: opens the KakaoTalk share window with a test message. If the domain is not registered in the Kakao
        // app, Kakao shows an error instead.
        document.getElementById('kakao-test')?.addEventListener('click', () => {
            const result = document.getElementById('kakao-test-result');

            try {
                if (!window.Kakao) throw new Error(<?= json_encode(lang('kakao_sdk_not_loaded'), JSON_UNESCAPED_UNICODE) ?>);
                if (!Kakao.isInitialized()) Kakao.init(<?= json_encode(vars('kakao_key')) ?>);

                Kakao.Share.sendDefault({
                    objectType: 'text',
                    text: <?= json_encode(lang('kakao_test_message'), JSON_UNESCAPED_UNICODE) ?>,
                    link: {mobileWebUrl: <?= json_encode(site_url()) ?>, webUrl: <?= json_encode(site_url()) ?>},
                });

                result.className = 'small mt-2 text-success';
                result.textContent = <?= json_encode(lang('kakao_test_opened'), JSON_UNESCAPED_UNICODE) ?>;
            } catch (error) {
                result.className = 'small mt-2 text-danger';
                result.textContent = error.message;
            }
        });
    });
</script>

<?php end_section('content'); ?>
