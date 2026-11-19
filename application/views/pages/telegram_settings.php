<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php $csrf_token = get_instance()->security->get_csrf_hash(); ?>

<div id="telegram-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="text-black-50 mb-3 fw-light">
                <i class="fab fa-telegram me-2"></i><?= lang('telegram') ?>
                <?php $chat_count = count(get_instance()->telegram_notifier->chat_ids()); ?>
                <?php if (vars('telegram_enabled') && vars('telegram_has_token') && $chat_count > 0): ?>
                    <span class="badge bg-success fs-6 fw-normal ms-2">
                        <?= strtr(lang('integration_configured_chats'), ['{count}' => $chat_count]) ?>
                    </span>
                <?php elseif (vars('telegram_has_token') || $chat_count > 0): ?>
                    <span class="badge bg-warning text-dark fs-6 fw-normal ms-2">
                        <?= strtr(lang('integration_off_chats'), ['{count}' => $chat_count]) ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary fs-6 fw-normal ms-2"><?= lang('integration_not_configured') ?></span>
                <?php endif; ?>
            </h4>

            <?php if (vars('flash_success')): ?>
                <div class="alert alert-success"><?= e(vars('flash_success')) ?></div>
            <?php endif; ?>

            <?php if (vars('flash_error')): ?>
                <div class="alert alert-danger"><?= e(vars('flash_error')) ?></div>
            <?php endif; ?>

            <?php if (vars('found_chats')): ?>
                <div class="alert alert-info">
                    <div class="fw-semibold mb-2"><?= lang('telegram_found_chats') ?></div>
                    <?php foreach (vars('found_chats') as $chat): ?>
                        <div>
                            <code class="user-select-all"><?= e($chat['id']) ?></code>
                            <span class="ms-2"><?= e($chat['name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="small mt-2"><?= lang('telegram_found_chats_hint') ?></div>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body small">
                    <div class="fw-semibold mb-2"><?= lang('telegram_how_to') ?></div>
                    <ol class="mb-0 ps-3">
                        <li><?= lang('telegram_step_1') ?></li>
                        <li><?= lang('telegram_step_2') ?></li>
                        <li><?= lang('telegram_step_3') ?></li>
                        <li><?= lang('telegram_step_4') ?></li>
                    </ol>
                </div>
            </div>

            <form method="post" action="<?= site_url('telegram_settings/save') ?>" id="telegram-settings-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="telegram-enabled" name="telegram_enabled"
                           value="1" <?= vars('telegram_enabled') ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="telegram-enabled">
                        <?= lang('telegram_enabled') ?>
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="telegram-bot-token"><?= lang('telegram_bot_token') ?></label>
                    <input type="password" class="form-control" id="telegram-bot-token" name="telegram_bot_token"
                           autocomplete="new-password" data-lpignore="true" data-1p-ignore placeholder="<?= vars('telegram_has_token')
                               ? e(lang('telegram_token_saved'))
                               : '123456789:ABC...' ?>">
                    <?php if (vars('telegram_has_token')): ?>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="remove-token" name="remove_token"
                                   value="1">
                            <label class="form-check-label small" for="remove-token">
                                <?= lang('telegram_remove_token') ?>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="telegram-chat-ids"><?= lang('telegram_chat_ids') ?></label>
                    <textarea class="form-control" id="telegram-chat-ids" name="telegram_chat_ids" rows="3"
                              placeholder="123456789"><?= e(vars('telegram_chat_ids')) ?></textarea>
                    <div class="form-text"><?= lang('telegram_chat_ids_hint') ?></div>
                </div>

                <div class="mb-4">
                    <div class="form-label"><?= lang('telegram_events') ?></div>
                    <?php foreach (vars('telegram_events') as $event => $checked): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="telegram-notify-<?= $event ?>"
                                   name="telegram_notify_<?= $event ?>" value="1" <?= $checked ? 'checked' : '' ?>>
                            <label class="form-check-label" for="telegram-notify-<?= $event ?>">
                                <?= lang('telegram_event_' . $event) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i><?= lang('save') ?>
                    </button>

                    <!-- Uses the token typed above (or the saved one), so it works before saving. -->
                    <button type="submit" class="btn btn-outline-secondary"
                            formaction="<?= site_url('telegram_settings/find_chats') ?>">
                        <i class="fas fa-search me-2"></i><?= lang('telegram_find_chats') ?>
                    </button>

                    <button type="submit" class="btn btn-outline-success"
                            formaction="<?= site_url('telegram_settings/test') ?>">
                        <i class="fab fa-telegram-plane me-2"></i><?= lang('telegram_send_test') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>
