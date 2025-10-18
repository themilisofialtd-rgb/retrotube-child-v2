<?php
/**
 * Template Part: Model Videos Section — Deep Meta Scan
 * Version: 2.7.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb, $post;

$model_name = get_the_title( $post->ID );
if ( empty( $model_name ) ) return;

error_log("[Model MetaScan] Running lookup for {$model_name}");

$cache_key = 'tmw_videos_' . sanitize_title( $model_name );
$video_ids = get_transient( $cache_key );

if ( false === $video_ids ) {
    $like = '%' . $wpdb->esc_like( $model_name ) . '%';

    $video_ids = $wpdb->get_col(
        $wpdb->prepare("
            SELECT DISTINCT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = 'video'
            AND p.post_status = 'publish'
            AND pm.meta_key IN ('partner','uploader','feed','_tmw_model_name')
            AND pm.meta_value LIKE %s
            LIMIT 40
        ", $like)
    );

    if ( $video_ids ) {
        set_transient( $cache_key, $video_ids, DAY_IN_SECONDS );
        error_log("[Model MetaScan] Found " . count($video_ids) . " videos for {$model_name}");
    } else {
        set_transient( $cache_key, array(), HOUR_IN_SECONDS );
        error_log("[Model MetaScan] No videos found for {$model_name}");
    }
}

if ( empty( $video_ids ) ) return;

$query = new WP_Query(array(
    'post_type'      => 'video',
    'post__in'       => $video_ids,
    'posts_per_page' => 12,
));

if ( $query->have_posts() ) :
?>
<section class="related-videos model-videos">
  <h3>Videos Featuring <?php echo esc_html( $model_name ); ?></h3>
  <div class="tmw-grid tmw-cols-3">
  <?php while ( $query->have_posts() ) : $query->the_post();
      get_template_part( 'template-parts/content', 'video' );
  endwhile; ?>
  </div>
</section>
<?php
wp_reset_postdata();
endif;
?>
