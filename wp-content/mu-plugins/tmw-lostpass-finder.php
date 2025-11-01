<?php
/**
 * TMW Lost Password Finder (audit only)
 * Visit any admin URL with ?tmw_find=lostpass while logged in as admin.
 */
if (!defined('ABSPATH')) exit;

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['tmw_find']) || $_GET['tmw_find'] !== 'lostpass') return;

    @ini_set('memory_limit', '512M');
    @set_time_limit(120);

    $roots = array_filter([
        WP_CONTENT_DIR . '/themes/retrotube',
        WP_CONTENT_DIR . '/themes/retrotube-child-v2',
        WP_PLUGIN_DIR   . '/wp-script-core',      // WP-Script Core plugin
        WP_PLUGIN_DIR   . '/retrotube',           // some stacks ship UI here
        WP_PLUGIN_DIR,                            // fallback: whole plugins dir (slower)
    ], 'is_dir');

    // strings to find (covers HTML, PHP, JS)
    $needles = [
        'Get new password', 'Get new pass', 'Lost Password', 'lostpassword',
        'wp-login.php?action=lostpassword', 'retrieve_password(', 'Password Reset'
    ];

    $ignoreDirs = ['/.git/', '/vendor/', '/node_modules/', '/cache/', '/uploads/', '/w3tc/', '/wp-scripts/'];
    $extRx = '~\.(php|phtml|html|twig|js|jsx|tsx)$~i';

    $hits = [];
    foreach ($roots as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (!preg_match($extRx, $path)) continue;
            $skip = false; foreach ($ignoreDirs as $ban) { if (strpos($path, $ban) !== false) { $skip = true; break; } }
            if ($skip) continue;

            $ln = 0;
            $matched = [];
            $fh = @fopen($path, 'r'); if (!$fh) continue;
            while (($line = fgets($fh)) !== false) {
                $ln++;
                foreach ($needles as $needle) {
                    if (stripos($line, $needle) !== false) {
                        $matched[] = [$ln, trim($line), $needle];
                    }
                }
            }
            fclose($fh);
            if ($matched) $hits[$path] = $matched;
        }
    }

    // Render report
    header('Content-Type: text/plain; charset=UTF-8');
    echo "=== [TMW-LOSTPASS-FIND] Report ===\n";
    echo "Time: " . date('c') . "\n\n";
    if (!$hits) {
        echo "No matches found. Try opening the popup and searching JS events. You can widen search needles inside the MU plugin.\n";
        exit;
    }
    foreach ($hits as $path => $rows) {
        echo "FILE: $path\n";
        foreach ($rows as [$ln, $line, $needle]) {
            $line = mb_substr($line, 0, 240);
            echo "  [$ln] ($needle)  $line\n";
        }
        echo "\n";
    }
    exit;
});
