<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mobile hero parity — late inline CSS + optional auditor.
 * Activation (query params):
 *   ?tmw-hero-fix=1      → print late mobile-only parity CSS (≤840px)
 *   ?tmw-hero-audit=1    → enqueue auditor JS (logs computed values)
 *   ?tmw-hero-force=1    → auditor will force inline !important for diagnosis
 */
function tmw_mobile_hero_page_is_single_model() {
    // Adjust if your CPT slug differs. This matches single-model.php usage.
    return function_exists('is_singular') && is_singular('model');
}

function tmw_mobile_hero_is_enabled() {
    return isset($_GET['tmw-hero-fix']) || isset($_GET['tmw-hero-audit']) || isset($_GET['tmw-hero-force']);
}

// Late, last-in CSS to beat any previous mobile overrides (desktop untouched)
add_action('wp_head', function () {
    if (!tmw_mobile_hero_page_is_single_model() || !isset($_GET['tmw-hero-fix'])) {
        return;
    }
    ?>
    <style id="tmw-mobile-hero-fix" data-source="tmw" media="all">
    /* v3.6.10 — Late inline parity CSS (≤840px); desktop untouched */
    @media (max-width: 840px) {
      .single-model .tmw-banner-container,
      .single-model .tmw-model-hero,
      .single-model .tmw-banner-frame { overflow: hidden; }

      .single-model .entry-header .tmw-banner-frame > img,
      .single-model .entry-header .tmw-banner-frame picture > img,
      .single-model .tmw-banner-frame > img,
      .single-model .tmw-banner-frame picture > img,
      .single-model .tmw-model-hero > img,
      .single-model .tmw-model-hero picture > img,
      .single-model .entry-header .wp-post-image,
      .single-model .tmw-banner-frame .wp-post-image {
        width: 100%; height: 100%; object-fit: cover;
        object-position: 50% calc(var(--tmw-hero-vpos, 50%) + var(--offset-y, 0px)) !important;
      }

      .single-model .entry-header .tmw-banner-frame,
      .single-model .tmw-banner-frame,
      .single-model .tmw-model-hero,
      .single-model .tmw-banner-container,
      .single-model .tmw-banner-frame::before,
      .single-model .tmw-model-hero::before {
        background-size: cover !important;
        background-position-x: 50% !important;
        background-position-y: calc(var(--tmw-hero-vpos, 50%) + var(--offset-y, 0px)) !important;
      }

      @supports not (object-position: 0 0) {
        .single-model .tmw-banner-frame > img,
        .single-model .tmw-banner-frame picture > img,
        .single-model .tmw-model-hero > img,
        .single-model .tmw-model-hero picture > img {
          transform: translateY(var(--offset-y, 0px)); will-change: transform;
        }
      }
    }
    </style>
    <?php
}, 99999);

// Optional auditor JS (only when asked)
add_action('wp_enqueue_scripts', function () {
    if (!tmw_mobile_hero_page_is_single_model() || !(isset($_GET['tmw-hero-audit']) || isset($_GET['tmw-hero-force']))) {
        return;
    }

    wp_enqueue_script(
        'tmw-mobile-hero-audit',
        get_stylesheet_directory_uri() . '/js/tmw-mobile-hero-audit.js',
        [],
        null,
        true
    );

    // Pass flags to JS
    wp_add_inline_script(
        'tmw-mobile-hero-audit',
        'window.TMW_MOBILE_HERO_FORCE_INLINE = ' . (isset($_GET['tmw-hero-force']) ? 'true' : 'false') . ';',
        'before'
    );
}, 99);
