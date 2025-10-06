<?php
/**
 * Template for displaying single Model posts
 * Fully matches RetroTube video layout but uses ACF model fields
 */

get_header();

if (have_posts()) :
while (have_posts()) : the_post();

  $model_id   = get_the_ID();
  $model_name = get_the_title();

  // ACF fields
  $banner_image      = get_field('banner_image', $model_id);
  $model_link        = get_field('model_link', $model_id);
  $flipbox_shortcode = get_field('flipbox_shortcode', $model_id);
  $bio               = get_field('model', $model_id);

  // Debug logging
  error_log('[ModelLayout] single-model.php loaded for ' . $model_name);
  if (!$banner_image)      error_log('[ModelLayout] No banner_image for ' . $model_name);
  if (!$model_link)        error_log('[ModelLayout] No model_link for ' . $model_name);
  if (!$flipbox_shortcode) error_log('[ModelLayout] No flipbox_shortcode for ' . $model_name);
  if (!$bio)               error_log('[ModelLayout] No model bio (ACF field "model") for ' . $model_name);
?>
<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">

    <!-- Title Block -->
    <div class="title-block box-shadow">
      <h1 class="entry-title"><?php echo esc_html($model_name); ?></h1>
    </div>

    <!-- Banner -->
    <?php if ($banner_image): ?>
      <?php
        // Handle both array or string formats
        $banner_url = is_array($banner_image) ? $banner_image['url'] : $banner_image;
      ?>
      <div class="video-player box-shadow">
        <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($model_name); ?>" class="aligncenter"/>
      </div>
    <?php endif; ?>

    <!-- Meta Strip -->
    <div class="video-meta-inline">
      <ul class="meta-list">
        <?php if ($model_link): ?>
          <li><a href="<?php echo esc_url($model_link); ?>" class="btn btn-primary" target="_blank">
            <i class="fa fa-video-camera"></i> <?php esc_html_e('Watch Live', 'retrotube'); ?>
          </a></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Tabs -->
    <div id="video-tabs" class="tabs">
      <ul class="tab-buttons">
        <li class="active"><a href="#tab-description"><?php esc_html_e('About', 'retrotube'); ?></a></li>
        <li><a href="#tab-videos"><?php esc_html_e('Videos', 'retrotube'); ?></a></li>
        <li><a href="#tab-comments"><?php esc_html_e('Comments', 'retrotube'); ?></a></li>
      </ul>

      <div class="tab-content">

        <!-- Tab: About -->
        <div id="tab-description" class="tab active">
          <div class="entry-content">
            <?php
              if ($bio) echo wpautop($bio);
              else the_content();

              if ($flipbox_shortcode) {
                echo '<div class="model-flipbox">';
                echo do_shortcode($flipbox_shortcode);
                echo '</div>';
              }

              if (has_tag()) :
                echo '<div class="post-tags">';
                the_tags('<strong>Tags:</strong> ', ', ');
                echo '</div>';
              endif;
            ?>
          </div>
        </div>

        <!-- Tab: Videos -->
        <div id="tab-videos" class="tab">
          <?php
          $related = new WP_Query([
            'post_type' => 'video',
            'posts_per_page' => 6,
            'meta_query' => [
              [
                'key'     => 'related_model',
                'value'   => $model_name,
                'compare' => 'LIKE',
              ]
            ]
          ]);
          if ($related->have_posts()) :
            echo '<div class="related-videos-grid">';
            while ($related->have_posts()) : $related->the_post();
              get_template_part('template-parts/loop', 'video');
            endwhile;
            echo '</div>';
            wp_reset_postdata();
          else :
            echo '<p>' . esc_html__('No videos found for this model.', 'retrotube') . '</p>';
          endif;
          ?>
        </div>

        <!-- Tab: Comments -->
        <div id="tab-comments" class="tab">
          <?php comments_template(); ?>
        </div>

      </div><!-- .tab-content -->
    </div><!-- #video-tabs -->

  </main>
</div>

<?php
endwhile;
endif;

get_footer();
?>
