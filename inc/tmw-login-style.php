<?php
if (!defined('ABSPATH')) exit;

/**
 * RetroTube visual for wp-login.php (applies inside our iframe)
 */
add_action('login_enqueue_scripts', function () {
    $primary = '#e31c3d'; // RetroTube accent
    $dark    = '#0d0d0d';
    $muted   = '#bfbfbf';
    ?>
    <style>
      body.login { background: #000; }
      body.login:before {
        content:""; position:fixed; inset:0;
        background: radial-gradient(1200px 600px at 50% -10%, #1a1a1a 0%, #0a0a0a 55%, #000 100%);
        opacity:.95; z-index:-1;
      }
      .login h1 a { background-image:none !important; text-indent:0 !important; font:600 22px/1.2 system-ui, -apple-system, Segoe UI, Roboto, Arial; color:#fff !important; width:auto; height:auto; }
      .login form { background: <?php echo $dark; ?>; border:1px solid #1c1c1c; box-shadow: 0 10px 30px rgba(0,0,0,.35); }
      .login form .input, .login input[type="text"], .login input[type="password"] {
        background:#131313; border:1px solid #252525; color:#fff;
      }
      .login .message, .login #login_error, .login .success {
        background:#101010; color:#eaeaea; border-left:4px solid <?php echo $primary; ?>;
      }
      .login .button-primary {
        background: <?php echo $primary; ?> !important; border-color: <?php echo $primary; ?> !important;
        text-shadow:none !important; box-shadow:none !important;
      }
      .login #nav a, .login #backtoblog a { color: <?php echo $muted; ?> !important; }
      .privacy-policy-page-link { display:none; } /* keep it clean inside modal */
    </style>
    <?php
});
