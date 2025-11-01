<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) { exit; }

/**
 * RetroTube Child (Flipbox Edition) v2 — Bootstrap
 * v4.1.0: move logic into /inc without behavior change.
 */
define('TMW_CHILD_VERSION', '4.1.0');
define('TMW_CHILD_PATH', get_stylesheet_directory());
define('TMW_CHILD_URL',  get_stylesheet_directory_uri());

// Single include: all logic is now in /inc/bootstrap.php
require_once TMW_CHILD_PATH . '/inc/bootstrap.php';

// === TMW Register Audit (audit-only) ===
if (!defined('TMW_REG_AUDIT')) { define('TMW_REG_AUDIT', true); }
if (TMW_REG_AUDIT && file_exists(get_stylesheet_directory() . '/inc/tmw-register-audit.php')) {
    require_once get_stylesheet_directory() . '/inc/tmw-register-audit.php';
}

// === AJAX login/register overrides ===
$tmw_ajax_auth = get_stylesheet_directory() . '/inc/ajax-login-register.php';
if (file_exists($tmw_ajax_auth)) {
    require_once $tmw_ajax_auth;
}

/** TMW Lost Password — FIX (routes popup → core, stops loader) */
$tmw_lp_fix = get_stylesheet_directory() . '/inc/tmw-lostpass-fix.php';
if (file_exists($tmw_lp_fix)) { require_once $tmw_lp_fix; }

// TEMP: disable email activation module
// if (file_exists(get_stylesheet_directory() . '/inc/tmw-email-activation.php')) {
//     require_once get_stylesheet_directory() . '/inc/tmw-email-activation.php';
// }

// === TMW Mail Transport (SMTP + logging) ===
if (file_exists(get_stylesheet_directory() . '/inc/tmw-mail-transport.php')) {
    require_once get_stylesheet_directory() . '/inc/tmw-mail-transport.php';
}

// === [Codex] One-shot loader for the Structure Audit (admin-only, safe) ===
add_action('admin_init', function () {
    if (!is_user_logged_in() || !current_user_can('manage_options')) { return; }
    if (!isset($_GET['tmw_audit']) || $_GET['tmw_audit'] !== 'structure') { return; }

    $audit = get_stylesheet_directory() . '/CODEX_THEME_STRUCTURE_AUDIT.php';
    if (file_exists($audit)) {
        require_once $audit;
        if (function_exists('tmw_theme_structure_audit')) {
            tmw_theme_structure_audit(true); // echoes plain text summary
            exit;
        }
    }
});

// Load Codex Reports admin viewer (read-only)
if (is_admin()) {
    $viewer = get_stylesheet_directory() . '/inc/admin/codex-reports-viewer.php';
    if (file_exists($viewer)) { require_once $viewer; }
}

// Load header→H1 gap diagnostic (read-only)
$gap_audit = get_stylesheet_directory() . '/inc/audit-header-gap.php';
if (file_exists($gap_audit)) { require_once $gap_audit; }


// Load FULL audit for header→H1 gap (report only, admin + flag)
$tmw_full = get_stylesheet_directory() . '/inc/audit-header-gap-full.php';
if (file_exists($tmw_full)) { require_once $tmw_full; }

// === TMW Lost Password — AUDIT ONLY (no behavior change) ===
$tmw_lostpass_audit = get_stylesheet_directory() . '/inc/tmw-lostpass-audit.php';
if (file_exists($tmw_lostpass_audit)) { require_once $tmw_lostpass_audit; }

// Lost-password override (child only) — disabled for audit
// $tmw_lostpass = get_stylesheet_directory() . '/inc/tmw-lostpass-override.php';
// if (file_exists($tmw_lostpass)) { require_once $tmw_lostpass; }
