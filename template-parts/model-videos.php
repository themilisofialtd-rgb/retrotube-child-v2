<?php
/**
 * Template Part: Model Videos Section — Auto-Discovery Version
 * v2.7.2
 */

global $post, $wpdb;
$model_name = get_the_title($post->ID);
$detected_meta_key = get_transient('tmw_detected_model_meta_key');

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
        set_transient('tmw_detected_model_meta_key', $meta_key, DAY_IN_SECONDS);
        error_log('[Model Video Audit] Found model link for '.$model_name.' under meta key: '.$meta_key);
        $detected_meta_key = $meta_key;
    } else {
        error_log('[Model Video Audit] No explicit meta key found for '.$model_name.', falling back to broad scan.');
    }
}

// === Build dynamic query ===
$meta_query = $detected_meta_key ? [
    [
        'key'     => $detected_meta_key,
        'value'   => $model_name,
        'compare' => 'LIKE',
    ]
] : [
    [
        'key'     => false,
        'value'   => $model_name,
        'compare' => 'LIKE',
    ]
];

$args = [
    'post_type'      => 'video',
    'posts_per_page' => 8,
    'meta_query'     => $meta_query,
];

// === Execute query ===
$query = new WP_Query($args);

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
