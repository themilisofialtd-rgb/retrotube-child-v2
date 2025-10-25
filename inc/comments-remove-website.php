<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Remove the "Website" (URL) field from ALL WordPress comment forms,
 * and scrub any submitted/displayed author URL for defense-in-depth.
 */

// 1) Remove from the default fields (affects non-logged-in comment form).
add_filter('comment_form_default_fields', function (array $fields) {
    if (isset($fields['url'])) {
        unset($fields['url']);
    }
    return $fields;
}, 999);

// 2) If any plugin tries to render it explicitly, neutralize the field.
add_filter('comment_form_field_url', '__return_empty_string', 999);

// 3) Scrub incoming data, in case a cached page or plugin still posts a URL.
add_filter('preprocess_comment', function (array $commentdata) {
    $commentdata['comment_author_url'] = '';
    return $commentdata;
}, 10);

// 4) Scrub on output (front end/admin lists).
add_filter('get_comment_author_url', '__return_empty_string', 10);
