<?php
/**
 * Template Name: Single Model
 * Description: Displays single model banner and related videos.
 */

error_log('[TMW-MODEL] single-model.php loaded for ' . get_the_title());

get_header(); ?>

<div id="primary" class="content-area with-sidebar-right">
  <main id="main" class="site-main with-sidebar-right" role="main">
    <?php
    if (have_posts()) :
      while (have_posts()) :
        the_post();

        // === Video & Tag Resolution ===
        $post_id    = get_the_ID();
        $model_slug = get_post_field('post_name', $post_id);
        $video_tags = [];

        if (function_exists('tmw_get_videos_for_model')) {
          $videos = tmw_get_videos_for_model($model_slug, -1);
          $video_count = is_array($videos) ? count($videos) : 0;
          error_log('[TMW-MODEL] Found ' . $video_count . ' videos for model: ' . get_the_title());

          if (!empty($videos)) {
            foreach ($videos as $video_post) {
              $tags_for_video = wp_get_post_terms($video_post->ID, 'post_tag');
              if (!is_wp_error($tags_for_video) && !empty($tags_for_video)) {
                foreach ($tags_for_video as $tag_term) {
                  $video_tags[$tag_term->term_id] = $tag_term;
                }
              }
            }
          }
        }

        $tag_count = count($video_tags);
        error_log('[TMW-MODEL] Found ' . $tag_count . ' tags for model: ' . get_the_title());

        if ($tag_count > 0) {
          uasort($video_tags, static function($a, $b) {
            return strcasecmp($a->name, $b->name);
          });
        }

        set_query_var('tmw_model_tags_data', array_values($video_tags));
        set_query_var('tmw_model_tags_count', $tag_count);
        error_log('[TMW-MODEL-TAGS-AUDIT] Model tags fully synchronized with video tags (v3.3.1).');

        // Capture content from model template part
        ob_start();
        get_template_part('template-parts/content', 'model');
        $model_content = ob_get_clean();

        echo $model_content; // Output final assembled model content

        set_query_var('tmw_model_tags_data', []);
        set_query_var('tmw_model_tags_count', null);
      endwhile;
    endif;
    ?>
  </main>
</div>

<?php get_sidebar(); ?>

<?php
// === [TMW-MODEL-COMMENTS] Inject comment section identical to single video ===
error_log('[TMW-MODEL-COMMENTS] Comment section loaded for ' . get_the_title());

if ( comments_open() || get_comments_number() ) : ?>
  <section id="comments" class="comments-area">
    <div class="entry-comments">
      <header class="entry-header">
        <h3 class="entry-title">
          <?php esc_html_e( 'Leave a Reply', 'wpst' ); ?>
        </h3>
      </header>
      <div class="entry-content">
        <?php comments_template(); ?>
      </div>
    </div>
  </section>
<?php else : ?>
  <?php error_log('[TMW-MODEL-COMMENTS] Comments closed or none found for ' . get_the_title()); ?>
<?php endif; ?>

<?php get_footer(); ?>
