<?php
/**
 * Template Part: Model Videos Section — Deep Meta Scan
 * v2.7.5
 */

global $post, $wpdb;
$model_name        = get_the_title($post->ID);
$model_slug        = sanitize_title($model_name);
$meta_cache_key    = 'tmw_detected_model_meta_key_' . $model_slug;
$tax_cache_key     = 'tmw_detected_model_taxonomy_' . $model_slug;
$detected_meta_key = get_transient($meta_cache_key);
$detected_taxonomy = get_transient($tax_cache_key);
$no_meta_key_cache = false;

if ('__no_match__' === $detected_meta_key) {
    $no_meta_key_cache = true;
    $detected_meta_key = false;
}

if ( ! function_exists('tmw_model_audit_trim_value') ) {
    function tmw_model_audit_trim_value($value) {
        $value = is_string($value) ? trim((function_exists('wp_strip_all_tags') ? wp_strip_all_tags($value) : strip_tags($value))) : '';

        if (strlen($value) > 80) {
            $value = (function_exists('mb_substr') ? mb_substr($value, 0, 77) : substr($value, 0, 77)) . '…';
        }

        return $value;
    }
}

if ( ! function_exists('tmw_model_audit_format_path') ) {
    function tmw_model_audit_format_path($path, $value) {
        if (empty($path)) {
            $value = tmw_model_audit_trim_value($value);

            if (preg_match('/model[^a-z0-9]*[\"\':=]+\s*(\"?)([^"\';]+)\1/i', $value, $match)) {
                return 'model=' . tmw_model_audit_trim_value($match[2]);
            }

            return $value;
        }

        $segments = array_map(
            function ($segment) {
                return is_string($segment) ? $segment : '[' . $segment . ']';
            },
            $path
        );

        $last = array_pop($segments);
        $last .= '=' . tmw_model_audit_trim_value($value);

        if ($segments) {
            $segments[] = $last;

            return implode(' → ', $segments);
        }

        return $last;
    }
}

if ( ! function_exists('tmw_model_audit_collect_matches') ) {
    function tmw_model_audit_collect_matches($data, $needle, $path = []) {
        $matches = [];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $segment = is_int($key) ? '[' . $key . ']' : (string) $key;
                $matches = array_merge(
                    $matches,
                    tmw_model_audit_collect_matches($value, $needle, array_merge($path, [$segment]))
                );
            }

            return $matches;
        }

        if (is_object($data)) {
            return tmw_model_audit_collect_matches(get_object_vars($data), $needle, $path);
        }

        if (!is_string($data)) {
            return $matches;
        }

        if (false !== stripos($data, $needle)) {
            $matches[] = tmw_model_audit_format_path($path, $data);

            return $matches;
        }

        $maybe_serialized = maybe_unserialize($data);

        if ($maybe_serialized !== $data) {
            $matches = array_merge($matches, tmw_model_audit_collect_matches($maybe_serialized, $needle, $path));
        }

        $decoded = null;
        $trimmed = trim($data);

        if ($trimmed) {
            $maybe_first = substr($trimmed, 0, 1);

            if (in_array($maybe_first, ['{', '[', '"'], true)) {
                $decoded = json_decode($trimmed, true);

                if (JSON_ERROR_NONE === json_last_error() && $decoded !== $data) {
                    $matches = array_merge($matches, tmw_model_audit_collect_matches($decoded, $needle, $path));
                }
            }
        }

        return $matches;
    }
}

// === Deep Model–Video Relationship Scan ===
if (false === $detected_meta_key && ! $no_meta_key_cache) {
    error_log('[Model Video Audit] Deep scan started for ' . $model_name);

    $deep_meta_rows = $wpdb->get_results(
        "SELECT pm.meta_key, pm.meta_value
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type = 'video' AND p.post_status = 'publish'"
    );

    $match_found = false;

    if ($deep_meta_rows) {
        foreach ($deep_meta_rows as $row) {
            $raw_value = $row->meta_value;
            $structures = [$raw_value];
            $unserialized = maybe_unserialize($raw_value);

            if ($unserialized !== $raw_value) {
                $structures[] = $unserialized;
            }

            $trimmed = is_string($raw_value) ? trim($raw_value) : '';

            if ($trimmed) {
                $decoded = json_decode($trimmed, true);

                if (JSON_ERROR_NONE === json_last_error() && $decoded !== $raw_value) {
                    $structures[] = $decoded;
                }
            }

            foreach ($structures as $structure) {
                $matches = tmw_model_audit_collect_matches($structure, $model_name);

                if (! $matches) {
                    continue;
                }

                $match_found      = true;
                $detected_meta_key = $row->meta_key;
                set_transient($meta_cache_key, $detected_meta_key, WEEK_IN_SECONDS);
                error_log('[Model Video Audit] Deep meta match: ' . $row->meta_key . ' → ' . reset($matches));

                break 2;
            }
        }
    }

    if (! $match_found) {
        set_transient($meta_cache_key, '__no_match__', WEEK_IN_SECONDS);
        error_log('[Model Video Audit] Deep scan found no serialized or JSON match for ' . $model_name);
    }
}

if (false === $detected_meta_key && $no_meta_key_cache) {
    $detected_meta_key = false;
}

if ( ! $detected_taxonomy ) {
    $taxonomies = get_object_taxonomies('video');

    if ( $taxonomies ) {
        foreach ( $taxonomies as $tax ) {
            $term = get_term_by('slug', $model_slug, $tax);

            if ( ! $term ) {
                $term = get_term_by('name', $model_name, $tax);
            }

            if ( $term ) {
                $detected_taxonomy = [
                    'taxonomy' => $tax,
                    'term'     => $term->slug,
                ];

                set_transient($tax_cache_key, $detected_taxonomy, DAY_IN_SECONDS);
                error_log('[Model Video Audit] Possible taxonomy match: ' . $tax . ' → ' . $term->slug);

                break;
            }
        }
    }
}

// === Auto-detect correct meta key if not cached ===
if ( ! $detected_meta_key ) {
    $meta_key = $wpdb->get_var(
        $wpdb->prepare("
            SELECT meta_key FROM {$wpdb->postmeta}
            WHERE meta_value LIKE %s
              AND post_id IN (
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'video' AND post_status = 'publish'
              )
            LIMIT 1
        ", '%' . $wpdb->esc_like($model_name) . '%')
    );

    if ( $meta_key ) {
        set_transient($meta_cache_key, $meta_key, WEEK_IN_SECONDS);
        error_log('[Model Video Audit] Found model link for '.$model_name.' under meta key: '.$meta_key);
        $detected_meta_key = $meta_key;
    } else {
        error_log('[Model Video Audit] No explicit meta key found for '.$model_name.', falling back to broad scan.');
    }
}

// === Build dynamic query ===
$query_args = [
    'post_type'      => 'video',
    'posts_per_page' => 8,
];

if ( $detected_meta_key ) {
    $query_args['meta_query'] = [
        [
            'key'     => $detected_meta_key,
            'value'   => $model_name,
            'compare' => 'LIKE',
        ],
    ];
} elseif ( $detected_taxonomy && isset($detected_taxonomy['taxonomy'], $detected_taxonomy['term']) ) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => $detected_taxonomy['taxonomy'],
            'field'    => 'slug',
            'terms'    => $detected_taxonomy['term'],
        ],
    ];
} else {
    $query_args['meta_query'] = [
        [
            'key'     => false,
            'value'   => $model_name,
            'compare' => 'LIKE',
        ],
    ];
}

// === Execute query ===
$query = new WP_Query($query_args);

if ($query->have_posts()) :
?>
<section class="tmw-model-videos">
  <h2 class="tmw-section-header">
    🎬 Videos with <?php echo esc_html($model_name); ?>
  </h2>
  <div class="tmw-grid tmw-cols-4">
    <?php
    while ($query->have_posts()) :
      $query->the_post();
      get_template_part('template-parts/content', 'video');
    endwhile;
    wp_reset_postdata();
    ?>
  </div>
</section>
<?php
else :
  error_log('[Model Video Audit] No matching videos found for '.$model_name);
endif;
?>
