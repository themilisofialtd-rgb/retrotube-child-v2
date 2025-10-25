<?php
// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

/**
 * Remove the "Website" (URL) field from comment forms on Video and Model singles.
 * Keeps Name and Email as-is.
 * Hardened against plugins that try to re-add the URL field.
 */
function tmw_is_video_or_model_context(): bool {
    if (is_admin()) { return false; }
    // Works both during template render and AJAX preloading
    $post = get_post();
    if (!$post) { return false; }
    $pt = get_post_type($post);
    return in_array($pt, array('video', 'model'), true);
}

/** Primary removal: drop 'url' from the default fields array */
add_filter('comment_form_default_fields', function (array $fields) {
    if (!tmw_is_video_or_model_context()) {
        return $fields;
    }
    if (isset($fields['url'])) {
        unset($fields['url']);
    }
    return $fields;
}, 10);

/** Hardening: even if some plugin re-injects it, neutralize the URL field output */
add_filter('comment_form_field_url', function ($field) {
    if (!tmw_is_video_or_model_context()) {
        return $field;
    }
    return ''; // delete at source
}, 10);

