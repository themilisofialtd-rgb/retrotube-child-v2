<?php
if (!defined('ABSPATH')) exit;

/**
 * TMW Lost Password / Login proxy in popup (embed mode)
 * - Opens wp-login endpoints inside our modal via iframe
 * - Rewrites reset email link to bounce via front-end so it opens in the modal
 * - Adds cache-busting & redirect_to
 */

if (!defined('TMW_LOSTPASS_MODE')) {
    define('TMW_LOSTPASS_MODE', 'embed'); // force popup
}

if (!defined('TMW_ACCOUNT_URL')) {
    // Where non-admins land after login; change via filter below.
    define('TMW_ACCOUNT_URL', home_url('/'));
}

function tmw_account_url() {
    return apply_filters('tmw/account_url', TMW_ACCOUNT_URL);
}

function tmw_lostpass_url() : string {
    // Include redirect_to so the wp-login "Log in" link knows where to go after auth
    $url = wp_lostpassword_url( tmw_account_url() );
    $url = add_query_arg('_tmwts', time(), $url); // defeat CDN cache
    return $url;
}

add_action('wp_enqueue_scripts', function () {
    $handle   = 'tmw-lostpass-proxy';
    $base_uri = get_stylesheet_directory_uri();

    wp_register_script(
        $handle,
        $base_uri . '/js/tmw-lostpass-proxy.js',
        array(),
        '4.10.8',
        true
    );

    // Selectors that can trigger the popup (links/buttons)
    $selectors = array(
        'a[href*="lostpassword"]',
        'a[href*="#lostpassword"]',
        'a[href*="forgot"]',
        'a.tmw-lostpass',
        'button.tmw-lostpass',
        '#tmw-lostpass-trigger',
        'a[href*="wp-login.php"]' // catches "login page" link on checkemail screens
    );

    wp_localize_script($handle, 'TMW_LOSTPASS', array(
        'mode'       => TMW_LOSTPASS_MODE,        // always 'embed'
        'lost_url'   => tmw_lostpass_url(),       // /wp-login.php?action=lostpassword&redirect_to=...
        'account'    => tmw_account_url(),
        'selectors'  => $selectors,
        'is_login'   => (bool) (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-login.php') !== false),
    ));
    wp_enqueue_script($handle);
}, 11);

/**
 * Simple modal container (dark, minimalist). We keep inline styles to avoid CSS collisions.
 */
add_action('wp_footer', function () {
    if (TMW_LOSTPASS_MODE !== 'embed') return; ?>
    <div id="tmw-lostpass-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99999;backdrop-filter:blur(2px)">
        <div id="tmw-lostpass-box" style="width:min(560px,92vw);height:min(680px,90vh);margin:5vh auto;background:#0d0d0d;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.45);position:relative">
            <button id="tmw-lostpass-close" aria-label="Close" style="position:absolute;top:8px;right:8px;border:0;background:#1f1f1f;color:#fff;padding:8px 10px;border-radius:10px;cursor:pointer">✕</button>
            <iframe id="tmw-lostpass-frame" src="about:blank" style="width:100%;height:100%;border:0;display:block" loading="lazy" referrerpolicy="no-referrer"></iframe>
        </div>
    </div>
<?php });

/**
 * Rewrite the password reset email so the link opens our site and auto-opens the modal
 * with the real rp URL in a query param (encoded).
 */
add_filter('retrieve_password_message', function ($message, $key, $user_login, $user_data) {
    $rp = network_site_url('wp-login.php?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user_login), 'login');
    $front = add_query_arg('tmw_reset', rawurlencode($rp), home_url('/')); // e.g. https://site/?tmw_reset=<encoded rp>

    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    $lines = array();
    $lines[] = sprintf(__('Someone requested a password reset for the following account on %s:'), $blogname);
    $lines[] = '';
    $lines[] = sprintf(__('Username: %s'), $user_login);
    $lines[] = '';
    $lines[] = __('To reset your password, click the link below (opens secure popup):');
    $lines[] = $front;
    $lines[] = '';
    $lines[] = __('If you did not request a password reset, please ignore this email.');
    return implode("\r\n", $lines);
}, 10, 4);
