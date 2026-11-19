<?php
/**
 * Kakao login button (Kakao design guide: yellow #FEE500, black symbol and text).
 *
 * @var string|null $return "my_appointments" to come back to "내 예약" after the login.
 */
?>
<a href="<?= site_url('kakao_login/start') . (!empty($return) ? '?return=' . rawurlencode($return) : '') ?>" class="btn fw-semibold px-4 py-2 d-inline-flex align-items-center gap-2"
   style="background: #FEE500; color: rgba(0, 0, 0, 0.85); border: none; border-radius: 12px;">
    <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#000" d="M12 3C6.48 3 2 6.48 2 10.77c0 2.79 1.86 5.23 4.66 6.6-.15.55-.98 3.5-1.01 3.73 0 0-.02.17.09.24.11.06.24.01.24.01.32-.04 3.7-2.43 4.28-2.85.57.08 1.15.12 1.74.12 5.52 0 10-3.48 10-7.77S17.52 3 12 3z"/>
    </svg>
    <?= lang('kakao_login') ?>
</a>
