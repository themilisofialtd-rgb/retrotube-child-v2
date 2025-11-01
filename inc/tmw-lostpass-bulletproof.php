<?php
/**
 * TMW Lost Password — BULLETPROOF adapter
 * - Owns RetroTube's popup endpoint and calls WP core retrieve_password()
 * - Emits JSON compatible with multiple legacy handlers to avoid UI loops
 * - Ships a tiny UI adapter to kill the "Loading..." spinner and print the message
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tmw_lp_log')) {
    function tmw_lp_log($msg, array $ctx = [])
    {
        $s = '[TMW-LOSTPASS-BP] ' . $msg;
        if ($ctx) {
            $s .= ' ' . wp_json_encode($ctx);
        }
        error_log($s);
    }
}

/**
 * Register earliest handlers for all variants the popup might use.
 * wp_send_json_* calls wp_die(), so once we answer, nothing else runs.
 */
add_action('init', function () {
    foreach ([
        'wp_ajax_nopriv_wpst_reset_password',
        'wp_ajax_wpst_reset_password',
        'wp_ajax_nopriv_wpst_lostpassword',
        'wp_ajax_wpst_lostpassword',
        'wp_ajax_nopriv_lostpassword',
        'wp_ajax_lostpassword',
    ] as $hook) {
        add_action($hook, 'tmw_lostpass_bp_handle', 0);
    }
}, 1);

/**
 * Main handler — sanitize input, call core, return universal JSON.
 */
function tmw_lostpass_bp_handle()
{
    nocache_headers();

    $raw = isset($_POST) ? wp_unslash($_POST) : [];
    $candidates = ['wpst_user_or_email', 'user_or_email', 'user_login', 'email', 'username'];
    $identifier = '';
    $source = '';
    $is_email = false;

    foreach ($candidates as $key) {
        if (empty($raw[$key])) {
            continue;
        }

        $value = trim((string) $raw[$key]);
        if ($value === '') {
            continue;
        }

        $candidate_is_email = is_email($value);

        if ($candidate_is_email || $key === 'email') {
            $sanitized = sanitize_email($value);
            $candidate_is_email = $sanitized !== '';
        } else {
            $sanitized = sanitize_user($value, true);
        }

        if ($sanitized === '') {
            continue;
        }

        $identifier = $sanitized;
        $source = $key;
        $is_email = $candidate_is_email;
        break;
    }

    tmw_lp_log('request', [
        'source' => $source ?: 'none',
        'type'   => $identifier === '' ? 'missing' : ($is_email ? 'email' : 'user_login'),
        'length' => $identifier === '' ? 0 : strlen($identifier),
    ]);

    if ($identifier === '') {
        tmw_lp_log('missing');
        tmw_lostpass_bp_json([
            'ok'      => false,
            'code'    => 'missing',
            'message' => __('Enter a valid username or email.', 'wpst'),
        ]);
    }

    $result = retrieve_password($identifier);

    if (is_wp_error($result)) {
        $code = $result->get_error_code() ?: 'password_reset_error';
        tmw_lp_log('core_error:' . $code, [
            'type' => $is_email ? 'email' : 'user_login',
        ]);

        $message = wp_strip_all_tags($result->get_error_message());
        if ($message === '') {
            $message = __('We were unable to process your request. Please try again.', 'wpst');
        }

        tmw_lostpass_bp_json([
            'ok'      => false,
            'code'    => $code,
            'message' => $message,
        ]);
    }

    tmw_lp_log('core_ok', [
        'type' => $is_email ? 'email' : 'user_login',
    ]);
    tmw_lp_log('mail_sent', [
        'type' => $is_email ? 'email' : 'user_login',
    ]);

    tmw_lostpass_bp_json([
        'ok'      => true,
        'code'    => 'mail_sent',
        'message' => __('Password Reset. Please check your email.', 'wpst'),
    ]);
}

/**
 * Emit JSON that both WP and RetroTube-style scripts accept.
 * Also duplicates message under common legacy keys to be future-proof.
 */
function tmw_lostpass_bp_json($result)
{
    $ok = !empty($result['ok']);
    $message = isset($result['message']) ? (string) $result['message'] : '';
    $code = isset($result['code']) ? (string) $result['code'] : '';

    $payload = array_merge(
        [
            'ok'       => $ok,
            'code'     => $code,
            'message'  => $message,
            'status'   => $ok ? 'ok' : 'error',
            'event'    => 'lostpassword',
            'msg'      => $message,
            'html'     => $message === '' ? '' : '<p class="alert alert-' . ($ok ? 'success' : 'danger') . '">' . esc_html($message) . '</p>',
            'redirect' => '',
            'reload'   => false,
            'refresh'  => false,
            'loggedin' => false,
        ],
        $result
    );

    if ($ok) {
        wp_send_json_success($payload);
    }

    wp_send_json_error($payload, 200);
}

/**
 * Tiny UI adapter: if any script fails to resolve the spinner,
 * catch the ajaxSuccess for wpst_reset_password and update the modal.
 */
add_action('wp_enqueue_scripts', function () {
    $script = <<<'JS'
    (function($){
        function handleLostPassResponse(raw){
            var data = raw && raw.data ? raw.data : raw;
            var msg  = (data && (data.message || data.msg || data.html)) || '';
            var $form = $('#wpst-reset-password');
            if (!$form.length) { return; }
            var $btn  = $form.find('button[type=submit]');
            var $status = $form.find('.tmw-ajax-status');
            if (!$status.length) { $status = $('<div class="tmw-ajax-status" />').prependTo($form); }
            if (msg) { $status.html(msg); }
            $btn.removeClass('disabled loading').prop('disabled', false);
            if ($btn.is('[data-loading-text]')) {
                $btn.text($btn.data('original-text') || $btn.text());
            }
            $('body').trigger('tmw:lostpassword:success');
        }

        $(document).on('ajaxSuccess', function(e, xhr, settings){
            if (!settings || !settings.url) return;
            if (settings.url.indexOf('action=wpst_reset_password') !== -1 ||
                settings.url.indexOf('action=wpst_lostpassword')  !== -1 ||
                settings.url.indexOf('action=lostpassword')        !== -1) {
                try { handleLostPassResponse(JSON.parse(xhr.responseText)); }
                catch(_) { handleLostPassResponse({}); }
            }
        });
    })(jQuery);
    JS;

    wp_add_inline_script('jquery', $script, 'after');
}, 9999);
