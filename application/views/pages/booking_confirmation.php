<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div class="d-flex align-items-center justify-content-center min-vh-100-">
    <div class="text-center py-4 px-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-4" style="width: 100px; height: 100px;">
            <i class="fas fa-calendar-check fa-3x text-success"></i>
        </div>

        <h3 class="text-success fw-semibold mb-4"><?= lang('appointment_registered') ?></h3>

        <pre id="appointment-share-text" class="text-start bg-light rounded p-3 mx-auto mb-3"
             style="white-space: pre-wrap; max-width: 420px; font-family: inherit;"><?= e(
                 vars('appointment_share_text'),
             ) ?></pre>

        <button type="button" id="share-kakaotalk" class="btn btn-lg px-4 py-2 fw-semibold"
                style="background-color: #FEE500; color: #191919; border: none;">
            <i class="fas fa-comment me-2"></i>
            <?= lang('share_appointment_kakaotalk') ?>
        </button>

        <p class="text-muted small mt-2 mb-4">
            <?= lang('share_appointment_kakaotalk_hint') ?>
        </p>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-4">
            <?php if (vars('kakao_logged_in')): ?>
                <a href="<?= site_url('my_appointments') ?>" class="btn px-4 py-2 fw-semibold"
                   style="background: #FEE500; color: rgba(0, 0, 0, 0.85);">
                    <i class="fas fa-list me-2"></i>
                    <?= lang('my_appointments') ?>
                </a>
            <?php endif; ?>

            <a href="<?= site_url() ?>" class="btn btn-primary px-4 py-2">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= lang('go_to_booking_page') ?>
            </a>

            <?php if (vars('display_add_to_google_calendar') === '1'): ?>
            <a href="<?= vars(
                'add_to_google_url',
            ) ?>" id="add-to-google-calendar" class="btn btn-outline-primary px-4 py-2" target="_blank">
                <i class="fab fa-google me-2"></i>
                <?= lang('add_to_google_calendar') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php if (vars('kakao_javascript_key')): ?>
    <script src="https://t1.kakaocdn.net/kakao_js_sdk/2.7.4/kakao.min.js" crossorigin="anonymous"></script>
<?php endif; ?>

<script>
    (function () {
        const kakaoKey = <?= json_encode((string) vars('kakao_javascript_key'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const shareText = <?= json_encode((string) vars('appointment_share_text'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const manageUrl = <?= json_encode((string) (vars('appointment_manage_url') ?: vars('booking_url')), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const buttonTitle = <?= json_encode(lang('view_my_appointment'), JSON_UNESCAPED_UNICODE) ?>;
        const copiedMessage = <?= json_encode(lang('appointment_text_copied'), JSON_UNESCAPED_UNICODE) ?>;

        function copyText() {
            const done = () => alert(copiedMessage);

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shareText).then(done, () => window.prompt('', shareText));
            } else {
                window.prompt('', shareText);
            }
        }

        function share() {
            // KakaoTalk share: opens KakaoTalk, where the customer picks the friend or chat to send the text to.
            if (window.Kakao) {
                try {
                    if (!Kakao.isInitialized()) {
                        Kakao.init(kakaoKey);
                    }

                    Kakao.Share.sendDefault({
                        objectType: 'text',
                        text: shareText,
                        link: {mobileWebUrl: manageUrl, webUrl: manageUrl},
                        buttonTitle: buttonTitle,
                    });

                    return;
                } catch (error) {
                    console.error('KakaoTalk share failed:', error);
                }
            }

            // Fallback without a Kakao key: the phone's share sheet (KakaoTalk can be chosen there), else copy.
            if (navigator.share) {
                navigator.share({text: shareText}).catch(() => {});
                return;
            }

            copyText();
        }

        document.getElementById('share-kakaotalk').addEventListener('click', share);
    })();
</script>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
