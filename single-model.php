<?php
/**
 * Template: Single Model (RetroTube full layout clone)
 * 100% same markup and JS behavior as single-video.php
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

  error_log('[ModelLayout] RetroTube accordion layout loaded for ' . $model_name);
?>

<div id="primary" class="content-area with-sidebar-right">
  <main id="main" class="site-main with-sidebar-right" role="main">

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

      <header class="entry-header">
        <div class="title-block box-shadow">
          <h1 class="entry-title" itemprop="name"><?php echo esc_html($model_name); ?></h1>
        </div>

        <!-- Banner (replaces video player) -->
        <div class="video-player box-shadow">
          <?php if ($banner_image): ?>
            <?php $banner_url = is_array($banner_image) ? $banner_image['url'] : $banner_image; ?>
            <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($model_name); ?>" class="aligncenter" />
          <?php else: ?>
            <div class="no-banner-placeholder"><?php esc_html_e('No banner available yet.', 'retrotube'); ?></div>
          <?php endif; ?>
        </div>

        <!-- Meta strip identical to videos -->
        <div class="video-meta-inline">
          <span class="video-meta-item video-meta-model">
            <i class="fa fa-star"></i> <?php esc_html_e('Model:', 'retrotube'); ?> <?php echo esc_html($model_name); ?>
          </span>
          <?php if ($model_link): ?>
          <span class="video-meta-item">
            <a href="<?php echo esc_url($model_link); ?>" class="btn btn-primary" target="_blank">
              <i class="fa fa-video-camera"></i> <?php esc_html_e('Watch Live', 'retrotube'); ?>
            </a>
          </span>
          <?php endif; ?>
          <span class="video-meta-item video-meta-date">
            <i class="fa fa-calendar"></i> <?php echo get_the_date(); ?>
          </span>
        </div>
      </header>

      <!-- RetroTube Tabs / Accordion Structure -->
      <div id="video-tabs" class="tabs">
        <ul class="tab-buttons">
          <li class="active"><a href="#tab-description"><?php esc_html_e('About', 'retrotube'); ?></a></li>
          <li><a href="#tab-videos"><?php esc_html_e('Videos', 'retrotube'); ?></a></li>
          <li><a href="#tab-comments"><?php esc_html_e('Comments', 'retrotube'); ?></a></li>
        </ul>

        <div class="tab-content">
          <!-- About (Accordion area) -->
          <div id="tab-description" class="tab active">
            <div class="entry-content">

              <!-- Accordion wrapper -->
              <div class="accordion" id="modelAccordion">

                <!-- Bio -->
                <div class="accordion-item">
                  <h3 class="accordion-header" id="headingBio">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBio" aria-expanded="true" aria-controls="collapseBio">
                      <?php esc_html_e('About the Model', 'retrotube'); ?>
                    </button>
                  </h3>
                  <div id="collapseBio" class="accordion-collapse collapse show" aria-labelledby="headingBio" data-bs-parent="#modelAccordion">
                    <div class="accordion-body">
                      <?php
                        if ($bio) echo wpautop($bio);
                        else the_content();
                      ?>
                    </div>
                  </div>
                </div>

                <!-- Flipbox -->
                <?php if ($flipbox_shortcode): ?>
                <div class="accordion-item">
                  <h3 class="accordion-header" id="headingFlipbox">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFlipbox" aria-expanded="false" aria-controls="collapseFlipbox">
                      <?php esc_html_e('Gallery / Flipbox', 'retrotube'); ?>
                    </button>
                  </h3>
                  <div id="collapseFlipbox" class="accordion-collapse collapse" aria-labelledby="headingFlipbox" data-bs-parent="#modelAccordion">
                    <div class="accordion-body">
                      <?php echo do_shortcode($flipbox_shortcode); ?>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Tags -->
                <div class="accordion-item">
                  <h3 class="accordion-header" id="headingTags">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTags" aria-expanded="false" aria-controls="collapseTags">
                      <?php esc_html_e('Tags', 'retrotube'); ?>
                    </button>
                  </h3>
                  <div id="collapseTags" class="accordion-collapse collapse" aria-labelledby="headingTags" data-bs-parent="#modelAccordion">
                    <div class="accordion-body">
                      <?php
                        if (has_tag()) :
                          echo '<div class="post-tags">';
                          the_tags('<strong>Tags:</strong> ', ', ');
                          echo '</div>';
                        else :
                          echo '<p>' . esc_html__('No tags added yet.', 'retrotube') . '</p>';
                        endif;
                      ?>
                    </div>
                  </div>
                </div>
              </div><!-- /accordion -->

            </div><!-- /entry-content -->
          </div><!-- /tab-description -->

          <!-- Videos Tab -->
          <div id="tab-videos" class="tab">
            <div class="under-video-block">
              <h2 class="widget-title"><?php esc_html_e('Model’s Videos', 'retrotube'); ?></h2>
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
                echo '<p>' . esc_html__('No videos found for this model yet.', 'retrotube') . '</p>';
              endif;
              ?>
            </div>
          </div>

          <!-- Comments Tab -->
          <div id="tab-comments" class="tab">
            <?php comments_template(); ?>
          </div>

        </div><!-- /tab-content -->
      </div><!-- /#video-tabs -->

    </article>
  </main>
</div>

<!-- Sidebar -->
<?php get_sidebar(); ?>

<?php
endwhile;
endif;

get_footer();
?>
