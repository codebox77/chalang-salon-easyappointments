<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
$csrf_token = get_instance()->security->get_csrf_hash();
$provider_colors = vars('provider_colors');
$status_styles = vars('status_styles');
// Default colors for providers without one, so that each gets a different color right away.
$palette = ['#4e79a7', '#f28e2b', '#59a14f', '#e15759', '#b07aa1', '#76b7b2', '#edc948', '#ff9da7', '#9c755f', '#bab0ac'];
?>

<div id="calendar-colors-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="text-black-50 mb-3 fw-light"><?= lang('calendar_colors') ?></h4>

            <?php if (vars('flash_success')): ?>
                <div class="alert alert-success"><?= e(vars('flash_success')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('calendar_colors/save') ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <h5 class="fw-light mb-2"><?= lang('calendar_color_mode') ?></h5>
                <div class="border rounded p-3 mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="color_mode" id="color-mode-appointment"
                               value="appointment" <?= vars('color_mode') !== 'provider' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="color-mode-appointment">
                            <?= lang('calendar_color_mode_appointment') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="color_mode" id="color-mode-provider"
                               value="provider" <?= vars('color_mode') === 'provider' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="color-mode-provider">
                            <?= lang('calendar_color_mode_provider') ?>
                        </label>
                    </div>
                </div>

                <h5 class="fw-light mb-2"><?= lang('provider_colors') ?></h5>
                <div class="border rounded p-3 mb-4">
                    <?php if (empty(vars('providers'))): ?>
                        <span class="text-muted"><?= lang('no_records_found') ?></span>
                    <?php endif; ?>
                    <?php foreach (vars('providers') as $index => $provider): ?>
                        <?php $color = $provider_colors[$provider['id']] ?? $palette[$index % count($palette)]; ?>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <input type="color" class="form-control form-control-color" style="width: 3rem;"
                                   id="provider-color-<?= (int) $provider['id'] ?>"
                                   name="provider_colors[<?= (int) $provider['id'] ?>]" value="<?= e($color) ?>">
                            <label for="provider-color-<?= (int) $provider['id'] ?>">
                                <?= e(trim($provider['first_name'] . ' ' . $provider['last_name'])) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-text"><?= lang('provider_colors_hint') ?></div>
                </div>

                <h5 class="fw-light mb-2"><?= lang('status_colors') ?></h5>
                <div class="border rounded p-3 mb-4">
                    <div class="form-text mt-0 mb-3"><?= lang('status_colors_hint') ?></div>

                    <?php foreach (vars('statuses') as $index => $status): ?>
                        <?php $style = $status_styles[$status] ?? []; ?>
                        <div class="row g-2 align-items-center mb-2 status-style-row">
                            <div class="col-12 col-md-3 fw-semibold">
                                <span class="status-preview badge border" style="min-width: 80px;"><?= e($status) ?></span>
                            </div>
                            <div class="col-6 col-md-4 d-flex align-items-center gap-2">
                                <input class="form-check-input mt-0 status-use-color" type="checkbox"
                                       id="status-use-color-<?= $index ?>" name="status_styles[<?= $index ?>][use_color]"
                                       value="1" <?= !empty($style['color']) ? 'checked' : '' ?>>
                                <input type="color" class="form-control form-control-color status-color"
                                       style="width: 3rem;" name="status_styles[<?= $index ?>][color]"
                                       value="<?= e(($style['color'] ?? '') ?: '#9e9e9e') ?>">
                                <label class="small" for="status-use-color-<?= $index ?>">
                                    <?= lang('use_status_color') ?>
                                </label>
                            </div>
                            <div class="col-6 col-md-5">
                                <select class="form-select form-select-sm status-brightness"
                                        name="status_styles[<?= $index ?>][brightness]">
                                    <?php foreach (['normal', 'light', 'lighter'] as $brightness): ?>
                                        <option value="<?= $brightness ?>" <?= ($style['brightness'] ?? 'normal') ===
                                        $brightness
                                            ? 'selected'
                                            : '' ?>>
                                            <?= lang('brightness_' . $brightness) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-square me-2"></i><?= lang('save') ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Live preview of each status badge (same color rules as calendar_colors.js; base color = gray example).
    document.addEventListener('DOMContentLoaded', () => {
        const lightenBy = {normal: 0, light: 0.45, lighter: 0.75};

        const mix = (hex, amount) => {
            const value = parseInt(hex.slice(1), 16);
            const channels = [(value >> 16) & 255, (value >> 8) & 255, value & 255].map((c) => c + (255 - c) * amount);
            const luminance = (0.299 * channels[0] + 0.587 * channels[1] + 0.114 * channels[2]) / 255;
            return {
                background: '#' + channels.map((c) => Math.round(c).toString(16).padStart(2, '0')).join(''),
                text: luminance > 0.6 ? '#212529' : '#ffffff',
            };
        };

        document.querySelectorAll('.status-style-row').forEach((row) => {
            const update = () => {
                const useColor = row.querySelector('.status-use-color').checked;
                const base = useColor ? row.querySelector('.status-color').value : '#7cbae8';
                const colors = mix(base, lightenBy[row.querySelector('.status-brightness').value] || 0);
                const preview = row.querySelector('.status-preview');
                preview.style.background = colors.background;
                preview.style.color = colors.text;
                preview.style.borderColor = base;
            };

            row.querySelectorAll('input, select').forEach((input) => input.addEventListener('input', update));
            row.querySelectorAll('input, select').forEach((input) => input.addEventListener('change', update));
            update();
        });
    });
</script>

<?php end_section('content'); ?>
