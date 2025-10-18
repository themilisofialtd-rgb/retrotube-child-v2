<?php
/**
 * Template Part: Model Videos Section — Smart Auto-Link
 * Version: 2.7.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb, $post;

if ( ! $post instanceof WP_Post ) {
    return;
}

$model_name = get_the_title( $post->ID );

if ( empty( $model_name ) ) {
    return;
}

$cache_key     = 'tmw_model_videos_' . sanitize_title( $model_name );
$cached_videos = get_transient( $cache_key );

if ( false === $cached_videos ) {
    error_log( '[Model AutoLink] Scanning videos for model: ' . $model_name );

    $like        = '%' . $wpdb->esc_like( $model_name ) . '%';
    $meta_keys   = array( 'partner', 'uploader', 'feed', '_tmw_model_name' );
    $placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

    $sql = $wpdb->prepare(
        "SELECT DISTINCT post_id
         FROM {$wpdb->postmeta}
         WHERE meta_key IN ($placeholders)
           AND meta_value LIKE %s
         LIMIT 30",
        array_merge( $meta_keys, array( $like ) )
    );

    $video_ids = $wpdb->get_col( $sql );

    if ( $video_ids ) {
        $video_ids = array_map( 'absint', $video_ids );
        set_transient( $cache_key, $video_ids, DAY_IN_SECONDS );
        error_log( '[Model AutoLink] Found ' . count( $video_ids ) . ' video(s) for ' . $model_name );
    } else {
        $video_ids = array();
        set_transient( $cache_key, $video_ids, DAY_IN_SECONDS );
        error_log( '[Model AutoLink] No videos found for ' . $model_name );
    }
} else {
    $video_ids = $cached_videos;
}

if ( empty( $video_ids ) ) {
    return;
}

$query = new WP_Query(
    array(
        'post_type'      => 'video',
        'post__in'       => $video_ids,
        'orderby'        => 'post__in',
        'posts_per_page' => 12,
    )
);

if ( ! $query->have_posts() ) {
    wp_reset_postdata();
    return;
}
?>

<section class="related-videos model-videos">
  <h3>Videos with <?php echo esc_html( $model_name ); ?></h3>
  <div class="tmw-grid tmw-cols-3">
    <?php
    while ( $query->have_posts() ) :
        $query->the_post();
        get_template_part( 'template-parts/content', 'video' );
    endwhile;
    ?>
  </div>
</section>
<?php
wp_reset_postdata();
