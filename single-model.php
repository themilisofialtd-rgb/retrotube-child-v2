<?php
/**
 * Template: Single Model (Full RetroTube Layout with Sidebar)
 * Mirrors single-video.php structure and includes ACF integration
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

  // Debug
  error_log('[ModelLayout] Full layout WITH SIDEBAR loaded for ' . $model_name);
?>
<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">

    <!-- Title Block -->
    <div class="title-block box-shadow">
      <h1 class="entry-title"><?php echo esc_html($model_name); ?></h1>
    </div>

    <!-- Banner -->
    <?php if ($banner_image): ?>
      <?php $banner_url = is_array($banner_image) ? $banner_image['url'] : $banner_image; ?>
      <div class="video-player box-shadow">
        <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($model_name); ?>" class="aligncenter"/>
      </div>
    <?php endif; ?>

    <!-- Meta Strip -->
    <div class="video-meta-inline">
      <ul class="meta-list">
        <?php if ($model_link): ?>
          <li><a href="<?php echo esc_url($model_link); ?>" target="_blank" class="btn btn-primary">
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

        <!-- About Tab -->
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

        <!-- Videos Tab -->
        <div id="tab-videos" class="tab">
          <?php
          $related = new WP_Query([
            'post_type'      => 'video',
            'posts_per_page' => 6,
            'meta_query'     => [
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
            echo '<p>' . esc_html__('No videos found for this model yet.', 'retrotube') . '</p>';
          endif;
          ?>
        </div>

        <!-- Comments Tab -->
        <div id="tab-comments" class="tab">
          <?php comments_template(); ?>
        </div>

      </div><!-- .tab-content -->
    </div><!-- #video-tabs -->

  </main><!-- #main -->
</div><!-- #primary -->

<!-- Sidebar -->
<?php get_sidebar(); ?>

<?php
endwhile;
endif;

get_footer();
?>
