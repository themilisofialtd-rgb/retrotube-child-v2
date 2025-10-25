<?php
/**
 * Retrotube Child (Flipbox Edition)
 * - Flipboxes + AWE helpers + admin tools
 * - Models virtual template with Banner (1200x350)
 * - Admin: right column on Model edit (RankMath box)
 *
 * IMPORTANT: Single PHP block. Do not add another <?php inside this file.
 */

if (!defined('TMW_BANNER_DEBUG')) {
    define('TMW_BANNER_DEBUG', false);
}

require_once get_stylesheet_directory() . '/assets/php/tmw-hybrid-model-scan.php';

add_action('after_setup_theme', function () {
    add_image_size('tmw-model-hero-land', 1440, 810, true);
    add_image_size('tmw-model-hero-banner', 1200, 350, true);
});

require_once get_stylesheet_directory() . '/inc/tmw-style-injectors.php';
require_once get_stylesheet_directory() . '/inc/tmw-model-hooks.php';
require_once get_stylesheet_directory() . '/inc/tmw-video-hooks.php';
require_once get_stylesheet_directory() . '/inc/tmw-admin-tools.php';
/**
 * v3.3.6 — FEATURED MODELS heading audit (admin-only, on-demand).
 * Use: append ?tmw_fm_audit=1 on a single model page.
 */
add_action('wp_enqueue_scripts', function () {
    if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) return;
    if ( ! is_singular('model') ) return;
    if ( empty($_GET['tmw_fm_audit']) ) return;

    $js = <<<JS
    (function(){
      var candidates = [
        '.tmw-featured-slot .section-title',
        '.model-flipbox .tmwfm-heading'
      ];
      var found = null, selector = '';
      for (var i=0;i<candidates.length;i++){
        var el = document.querySelector(candidates[i]);
        if (el){ found = el; selector = candidates[i]; break; }
      }
      if (found){
        found.setAttribute('data-tmw-fm','heading');
        console.log('[TMW-FM-AUDIT] Heading matched selector:', selector, found);
      } else {
        console.warn('[TMW-FM-AUDIT] No FEATURED MODELS heading found with known selectors.');
      }
    })();
    JS;
    // Attach after a common handle so it runs reliably.
    wp_add_inline_script('jquery', $js, 'after');
}, 25);
