<?php
/**
 * Template Part: Model Videos Section
 * Shows videos tagged to the same model.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

if (!($post instanceof WP_Post)) {
    return;
}

$model_name = get_the_title($post->ID);

if ($model_name === '') {
    return;
}

$max_items = 8;

$video_ids = [];

$possible_taxonomies = ['models', 'model', 'actors', 'pornstar', 'video_model'];
$existing_taxonomy   = '';

$transient_key     = 'tmw_model_videos_meta_key';
$detected_meta_key = get_transient($transient_key);

foreach ($possible_taxonomies as $tax) {
    if (taxonomy_exists($tax)) {
        $existing_taxonomy = $tax;
        break;
    }
}

$taxonomy_posts = [];

if ($existing_taxonomy !== '') {
    $taxonomy_args = [
        'post_type'      => 'video',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => $max_items,
        'tax_query'      => [
            [
                'taxonomy' => $existing_taxonomy,
                'field'    => 'name',
                'terms'    => $model_name,
            ],
        ],
    ];

    $taxonomy_posts = get_posts($taxonomy_args);

    if (!empty($taxonomy_posts)) {
        $video_ids = $taxonomy_posts;
    }
}

if ($detected_meta_key === false) {
    error_log('Model video debug for ' . $model_name . ': ' . print_r(get_post_meta(get_the_ID()), true));

    $scan_args = [
        'post_type'      => 'video',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => false,
                'value'   => $model_name,
                'compare' => 'LIKE',
            ],
        ],
    ];

    $scan_posts = get_posts($scan_args);
    $found_key  = '';

    if (!empty($scan_posts)) {
        $video_meta = get_post_meta($scan_posts[0]);

        foreach ($video_meta as $meta_key => $meta_values) {
            foreach ((array) $meta_values as $meta_value) {
                $meta_value = maybe_unserialize($meta_value);

                if (is_array($meta_value)) {
                    $meta_value = wp_json_encode($meta_value);
                }

                if (stripos((string) $meta_value, $model_name) !== false) {
                    $found_key = $meta_key;
                    break 2;
                }
            }
        }
    }

    if ($found_key !== '') {
        set_transient($transient_key, $found_key, DAY_IN_SECONDS);
        error_log("Model video debug for {$model_name}: found meta key '{$found_key}'");
        $detected_meta_key = $found_key;
    } else {
        set_transient($transient_key, '__not_found__', HOUR_IN_SECONDS);
        error_log('Model video debug for ' . $model_name . ': no matching video meta key found');
        $detected_meta_key = '__not_found__';
    }
}

if (count($video_ids) < $max_items) {
    $remaining = $max_items - count($video_ids);

    if (!empty($detected_meta_key) && $detected_meta_key !== '__not_found__') {
        $meta_query = [
            [
                'key'     => $detected_meta_key,
                'value'   => $model_name,
                'compare' => 'LIKE',
            ],
        ];
    } else {
        $meta_query = [
            'relation' => 'OR',
            [
                'key'     => 'model_name',
                'value'   => $model_name,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'video_model',
                'value'   => $model_name,
                'compare' => 'LIKE',
            ],
        ];
    }

    $meta_args = [
        'post_type'      => 'video',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => $remaining,
        'meta_query'     => $meta_query,
    ];

    $meta_posts = get_posts($meta_args);

    if (!empty($meta_posts)) {
        $video_ids = array_merge($video_ids, $meta_posts);
    }
}

$video_ids = array_values(array_unique($video_ids));

if (empty($video_ids)) {
    return;
}

if (count($video_ids) > $max_items) {
    $video_ids = array_slice($video_ids, 0, $max_items);
}

$query_args = [
    'post_type'           => 'video',
    'post_status'         => 'publish',
    'post__in'            => $video_ids,
    'orderby'             => 'post__in',
    'posts_per_page'      => count($video_ids),
    'ignore_sticky_posts' => true,
];

$videos_query = new WP_Query($query_args);

if (!$videos_query->have_posts()) {
    wp_reset_postdata();
    return;
}
?>
<section class="tmw-model-videos">
    <div class="tmw-section-header"><span>🎬 Videos with <?php echo esc_html($model_name); ?></span></div>
    <div class="tmw-grid tmw-cols-4">
        <?php
        while ($videos_query->have_posts()) :
            $videos_query->the_post();
            get_template_part('template-parts/content', 'video');
        endwhile;
        ?>
    </div>
</section>
<?php
wp_reset_postdata();

