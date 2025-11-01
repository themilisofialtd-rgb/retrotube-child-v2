<?php
if (!defined('ABSPATH')) exit;
/**
 * TMW Lost Password — AUDIT ONLY (no behavior changes)
 * v1.0.1
 */

if (!defined('TMW_LOSTPASS_AUDIT_VERSION')) {
    define('TMW_LOSTPASS_AUDIT_VERSION', '1.0.1');
}

$tmw_lp_log = function ($msg) {
    if (is_array($msg) || is_object($msg)) {
        $msg = wp_json_encode($msg);
    }
    error_log('[TMW-LOSTPASS-AUDIT] ' . $msg);
};

/**
 * 1) Log exactly which AJAX hook fired and what POST keys arrived.
 *    Runs early, does not alter payload.
 */
foreach ([
    'wp_ajax_nopriv_wpst_reset_password',
    'wp_ajax_wpst_reset_password',
    'wp_ajax_nopriv_wpst_lostpassword',
    'wp_ajax_wpst_lostpassword',
    'wp_ajax_nopriv_lostpassword',
    'wp_ajax_lostpassword'
] as $hook) {
    add_action($hook, function () use ($tmw_lp_log, $hook) {
        $keys = implode(',', array_keys($_POST));
        $tmw_lp_log('action=' . $hook . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' keys=' . $keys);
    }, 1);
}

/**
 * 2) Trace allow_password_reset decisions.
 */
add_filter('allow_password_reset', function ($allow, $user_id) use ($tmw_lp_log) {
    $allow_str = is_wp_error($allow) ? ('WP_Error:' . $allow->get_error_code()) : var_export($allow, true);
    $tmw_lp_log('filter:allow_password_reset user_id=' . $user_id . ' allow=' . $allow_str);
    return $allow;
}, 10, 2);

/**
 * 3) Prove core retrieve_password() ran AND avoid previous fatal.
 *    WP core may call this filter with 4 args; RetroTube’s legacy may pass only 2.
 *    We accept 2..4 args safely to prevent ArgumentCountError.
 */
add_filter('retrieve_password_message', function ($message, $key, $user_login = '', $user = null) use ($tmw_lp_log) {
    $uid = (is_object($user) && isset($user->ID)) ? $user->ID : '';
    $tmw_lp_log(sprintf('core:retrieve_password user_login=%s key=%s uid=%s', $user_login, $key, $uid));
    return $message;
}, 10, 4);

/**
 * 4) Full mail pipeline audit
 */
add_filter('wp_mail', function ($args) use ($tmw_lp_log) {
    $to = is_array($args['to']) ? implode(';', $args['to']) : $args['to'];
    $headers = is_array($args['headers']) ? implode(';', $args['headers']) : (string) $args['headers'];
    $tmw_lp_log('wp_mail to=' . $to . ' subject=' . $args['subject'] . ' headers="' . $headers . '"');
    return $args;
}, 10);

add_action('wp_mail_failed', function ($error) use ($tmw_lp_log) {
    $tmw_lp_log('wp_mail_failed msg=' . $error->get_error_message());
}, 10, 1);

add_action('phpmailer_init', function ($phpmailer) use ($tmw_lp_log) {
    $tmw_lp_log('phpmailer_init mailer=' . $phpmailer->Mailer . ' host=' . $phpmailer->Host);
}, 10, 1);

/**
 * 5) Admin self-test: ?tmw_test=lostpass&user_login=<login>
 *    (Only for admins, no behavior change for visitors.)
 */
add_action('admin_init', function () use ($tmw_lp_log) {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!isset($_GET['tmw_test']) || $_GET['tmw_test'] !== 'lostpass') {
        return;
    }

    $user_login = sanitize_text_field($_GET['user_login'] ?? '');
    if (!$user_login) {
        wp_die('Missing user_login');
    }

    $tmw_lp_log('selftest begin user_login=' . $user_login);
    $u = get_user_by('login', $user_login);
    if (!$u) {
        wp_die('User not found');
    }

    // NOTE: retrieve_password() returns true|WP_Error in modern WP.
    $r = retrieve_password($u->user_login);
    $tmw_lp_log('selftest retrieve_password returned=' . var_export($r, true));
    wp_die('OK — see debug.log for audit lines.');
});
