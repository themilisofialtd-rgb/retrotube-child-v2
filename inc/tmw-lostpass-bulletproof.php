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
add_action('wp_ajax_nopriv_tmw_lostpass_bp', 'tmw_lostpass_bp_handle', 0);
add_action('wp_ajax_tmw_lostpass_bp', 'tmw_lostpass_bp_handle', 0);

/**
 * Main handler — sanitize input, call core, return universal JSON.
 */
function tmw_lostpass_bp_handle()
{
    nocache_headers();

    $raw = isset($_POST) ? wp_unslash($_POST) : [];

    $nonce = isset($raw['tmw_lostpass_bp_nonce']) ? $raw['tmw_lostpass_bp_nonce'] : '';
    if (!is_string($nonce) || !wp_verify_nonce($nonce, 'tmw_lostpass_bp_nonce')) {
        tmw_lp_log('ERR invalid nonce');
        tmw_lostpass_bp_json(
            false,
            '<p class="alert alert-danger">' . esc_html__('Security check failed. Please refresh and try again.', 'wpst') . '</p>',
            'invalid_nonce'
        );
    }
    $candidates = ['user_login', 'user_login_or_email', 'login', 'user_email', 'email', 'username'];
    $user_login = '';

    foreach ($candidates as $k) {
        if (!empty($raw[$k])) {
            $user_login = sanitize_text_field($raw[$k]);
            break;
        }
    }

    if ($user_login === '') {
        tmw_lp_log('ERR missing credential');
        tmw_lostpass_bp_json(
            false,
            '<p class="alert alert-danger">' . esc_html__('Missing username or email.', 'wpst') . '</p>',
            'missing_username_or_email'
        );
    }

    $r = retrieve_password($user_login);

    if (is_wp_error($r)) {
        tmw_lp_log('ERR core retrieve_password', ['code' => $r->get_error_code()]);
        tmw_lostpass_bp_json(
            false,
            '<p class="alert alert-danger">' . esc_html($r->get_error_message()) . '</p>',
            $r->get_error_code() ?: 'password_reset_error'
        );
    }

    tmw_lp_log('OK email sent', ['user_login' => $user_login]);
    tmw_lostpass_bp_json(
        true,
        '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>',
        'password_reset_email_sent'
    );
}

/**
 * Emit JSON that both WP and RetroTube-style scripts accept.
 * Also duplicates message under common legacy keys to be future-proof.
 */
function tmw_lostpass_bp_json($ok, $message, $code = '')
{
    $payload = [
        'message'  => $message,
        'status'   => $ok ? 'ok' : 'error',
        'event'    => 'lostpassword',
        'code'     => $code,
        'msg'      => $message,
        'html'     => $message,
        'redirect' => '',
        'reload'   => false,
        'refresh'  => false,
        'loggedin' => false,
    ];

    if ($ok) {
        wp_send_json_success($payload);
    }

    wp_send_json_error($payload, 200);
}

/**
 * Tiny UI adapter: if any script fails to resolve the spinner,
 * catch the ajaxSuccess for tmw_lostpass_bp and update the modal.
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
            if (settings.url.indexOf('action=tmw_lostpass_bp') !== -1) {
                try { handleLostPassResponse(JSON.parse(xhr.responseText)); }
                catch(_) { handleLostPassResponse({}); }
            }
        });
    })(jQuery);
    JS;

    wp_add_inline_script('jquery', $script, 'after');
}, 9999);
