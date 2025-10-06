<?php
/**
 * Template: Single Model
 * Cloned from parent single-video.php to ensure identical layout and sidebar
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

  // Log template load
  error_log('[ModelLayout] Cloned single-video layout loaded for ' . $model_name);
?>
<div id="primary" class="content-area with-sidebar-right">
  <main id="main" class="site-main with-sidebar-right" role="main">

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
      <header class="entry-header">

        <!-- Banner (replaces video player) -->
        <div class="video-player">
          <?php if ($banner_image): ?>
            <?php $banner_url = is_array($banner_image) ? $banner_image['url'] : $banner_image; ?>
            <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($model_name); ?>" class="aligncenter" />
          <?php else: ?>
            <div class="no-banner-placeholder"><?php esc_html_e('No banner image uploaded yet.', 'retrotube'); ?></div>
          <?php endif; ?>
        </div>

        <div class="title-block box-shadow">
          <h1 class="entry-title" itemprop="name"><?php echo esc_html($model_name); ?></h1>
          <div id="video-tabs" class="tabs">
            <button class="tab-link active about" data-tab-id="video-about">
              <i class="fa fa-info-circle"></i> <?php esc_html_e('About', 'retrotube'); ?>
            </button>
            <button class="tab-link share" data-tab-id="video-videos">
              <i class="fa fa-video-camera"></i> <?php esc_html_e('Videos', 'retrotube'); ?>
            </button>
            <button class="tab-link comments" data-tab-id="video-comments">
              <i class="fa fa-comments"></i> <?php esc_html_e('Comments', 'retrotube'); ?>
            </button>
          </div>
        </div>

        <div class="video-meta-inline">
          <span class="video-meta-item video-meta-model">
            <i class="fa fa-star"></i> <?php esc_html_e('Model:', 'retrotube'); ?> <?php echo esc_html($model_name); ?>
          </span>
          <span class="video-meta-item video-meta-author">
            <i class="fa fa-user"></i> <?php esc_html_e('From:', 'retrotube'); ?> <a href="/author/vivedore/">AdultWebmaster69</a>
          </span>
          <span class="video-meta-item video-meta-date">
            <i class="fa fa-calendar"></i> <?php echo get_the_date(); ?>
          </span>
        </div>

      </header>

      <div class="entry-content">
        <div id="video-about" class="width70">
          <div class="video-description">
            <?php
              if ($bio) echo wpautop($bio);
              else the_content();

              if ($flipbox_shortcode) {
                echo '<div class="model-flipbox">';
                echo do_shortcode($flipbox_shortcode);
                echo '</div>';
              }
            ?>
          </div>

          <!-- Tags -->
          <div class="tags">
            <?php if (has_tag()) : ?>
              <div class="tags-list"><?php the_tags('<i class="fa fa-tag"></i> ', ' ', ''); ?></div>
            <?php else : ?>
              <p class="no-tags"><?php esc_html_e('No tags available yet.', 'retrotube'); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Related Videos -->
        <div id="video-videos" class="width70">
          <div class="under-video-block">
            <h2 class="widget-title"><?php esc_html_e('Related Videos', 'retrotube'); ?></h2>
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
              echo '<p>' . esc_html__('No related videos found for this model.', 'retrotube') . '</p>';
            endif;
            ?>
          </div>
        </div>

        <!-- Comments -->
        <div id="video-comments" class="width70">
          <?php
            if (comments_open() || get_comments_number()) :
              comments_template();
            else :
              echo '<p>' . esc_html__('Comments are closed.', 'retrotube') . '</p>';
            endif;
          ?>
        </div>

      </div><!-- .entry-content -->

    </article>
  </main>
</div>

<!-- Sidebar identical to video pages -->
<?php get_sidebar(); ?>

<?php
endwhile;
endif;

get_footer();
?>
