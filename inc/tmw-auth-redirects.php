<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin lockout for non-admin roles + front-end login redirects + hide admin bar
 */

if (!defined('TMW_ACCOUNT_URL')) {
    define('TMW_ACCOUNT_URL', home_url('/'));
}

if (!function_exists('tmw_account_url')) {
    function tmw_account_url() {
        return apply_filters('tmw/account_url', TMW_ACCOUNT_URL);
    }
}

// After successful login, send non-admins to account/front-end URL regardless of redirect_to.
add_filter('login_redirect', function ($redirect_to, $request, $user) {
    if (is_wp_error($user) || !$user) return $redirect_to;
    if (user_can($user, 'manage_options')) return $redirect_to;
    return tmw_account_url();
}, 10, 3);

// Block direct wp-admin access for non-admins (but allow admin-ajax.php).
add_action('admin_init', function () {
    if (!is_user_logged_in()) return;
    if (current_user_can('manage_options')) return;
    if (wp_doing_ajax()) return;
    wp_safe_redirect(tmw_account_url());
    exit;
});

// Hide admin bar for non-admins.
add_filter('show_admin_bar', function ($show) {
    if (current_user_can('manage_options')) return $show;
    return false;
}, 99);
