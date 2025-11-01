<?php
if (!defined('ABSPATH')) { exit; }

// Bridge: keep legacy flipbox shortcodes and helpers until refactored.
$flipboxes_legacy = TMW_CHILD_PATH . '/inc/tmw-video-hooks.php';
if (is_readable($flipboxes_legacy)) {
    require_once $flipboxes_legacy;
}
