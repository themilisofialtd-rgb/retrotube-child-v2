<?php
// NOTE: This file is never loaded by WordPress automatically.
// Run manually via WP-CLI: 
//   wp eval-file wp-content/themes/retrotube-child-v2/CODEX_TOOL_resetkey_diag.php <username|email> [--reset=1]

if ( ! defined('ABSPATH') ) {
    // WP-CLI loads WordPress then includes this file. If called directly, bail.
    exit("Run me with: wp eval-file path/to/CODEX_TOOL_resetkey_diag.php <username|email> [--reset=1]\n");
}

// WP-CLI passes $args / $assoc_args into eval-file scope.
if ( ! isset($args) ) { $args = []; }
if ( ! isset($assoc_args) ) { $assoc_args = []; }

$who = $args[0] ?? null;
if ( ! $who ) {
    echo "Usage: wp eval-file wp-content/themes/retrotube-child-v2/CODEX_TOOL_resetkey_diag.php <username|email> [--reset=1]\n";
    return;
}

$user = get_user_by('login', $who);
if ( ! $user ) {
    $user = get_user_by('email', $who);
}
if ( ! $user ) {
    echo "[TMW-RP-DIAG] User not found for identifier: {$who}\n";
    return;
}

$uid     = (int) $user->ID;
$now     = time();
$rp_key  = get_user_meta($uid, 'rp_key', true);
$rp_exp  = (int) get_user_meta($uid, 'rp_key_expiration', true);
$lifesec = apply_filters('password_reset_expiration', DAY_IN_SECONDS);

echo "=== TMW Reset-Key Diagnostic ===\n";
echo "User        : {$user->user_login} (#{$uid})\n";
echo "Email       : {$user->user_email}\n";
echo "Server time : {$now} (" . gmdate('c', $now) . " UTC)\n";
echo "rp_key      : " . ($rp_key ? "present (hashed)" : "MISSING") . "\n";
echo "rp_exp      : {$rp_exp}" . ($rp_exp ? " (" . gmdate('c', $rp_exp) . " UTC)" : "") . "\n";
echo "Remaining   : " . ($rp_exp ? ($rp_exp - $now) . " sec" : "n/a") . "\n";
echo "Lifetime(flt password_reset_expiration) : " . var_export($lifesec, true) . "\n";

$status = ($rp_key && $rp_exp > $now) ? 'OK_VALID' : 'EXPIRED_OR_MISSING';
echo "Status      : {$status}\n";

if ( ! empty($assoc_args['reset']) ) {
    delete_user_meta($uid, 'rp_key');
    delete_user_meta($uid, 'rp_key_expiration');
    echo "[TMW-RP-DIAG] Cleared rp_key and rp_key_expiration for user #{$uid}. Request a fresh reset now.\n";
}
