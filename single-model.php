<?php
/**
 * Template: Single Model
 *
 * Cloned from the parent theme's single-video.php template to keep the exact
 * markup, styles, and JavaScript hooks that power the RetroTube layout.
 */

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right single-video">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemprop="video" itemscope itemtype="http://schema.org/VideoObject">
            <header class="entry-header">
              <?php
              // Use model banner instead of video player
              $banner = function_exists('get_field') ? get_field('banner_image') : '';
              if ($banner) {
                  $banner_url = is_array($banner) ? ($banner['url'] ?? '') : $banner;
                  if (!empty($banner_url)) {
                      echo '<div class="video-player box-shadow">';
                      echo '<img src="' . esc_url($banner_url) . '" alt="' . esc_attr(get_the_title()) . '" />';
                      echo '</div>';
                  } else {
                      echo '<div class="video-player box-shadow placeholder-banner"><p>' . esc_html__('No banner image uploaded yet.', 'retrotube') . '</p></div>';
                  }
              } else {
                  echo '<div class="video-player box-shadow placeholder-banner"><p>' . esc_html__('No banner image uploaded yet.', 'retrotube') . '</p></div>';
              }
              ?>

              <div class="title-block box-shadow">
                <?php the_title('<h1 class="entry-title" itemprop="name">', '</h1>'); ?>
                <?php if (function_exists('xbox_get_field_value') && xbox_get_field_value('wpst-options', 'enable-rating-system') == 'on') : ?>
                  <div id="rating">
                    <span id="video-rate"><?php echo wp_kses_post(wpst_get_post_like_link(get_the_ID())); ?></span>
                    <?php $is_rated_yet = (wpst_get_post_like_rate(get_the_ID()) === false) ? ' not-rated-yet' : ''; ?>
                  </div>
                <?php endif; ?>
                <div id="video-tabs" class="tabs">
                  <button class="tab-link active about" data-tab-id="video-about">
                    <i class="fa fa-info-circle"></i> <?php esc_html_e('About', 'retrotube'); ?>
                  </button>
                  <?php if (function_exists('xbox_get_field_value') && xbox_get_field_value('wpst-options', 'enable-video-share') == 'on') : ?>
                    <button class="tab-link share" data-tab-id="video-share">
                      <i class="fa fa-share"></i> <?php esc_html_e('Share', 'retrotube'); ?>
                    </button>
                  <?php endif; ?>
                  <button class="tab-link comments" data-tab-id="video-comments">
                    <i class="fa fa-comments"></i> <?php esc_html_e('Comments', 'retrotube'); ?>
                  </button>
                </div>
              </div>

              <div class="video-meta-inline">
                <span class="video-meta-item video-meta-model">
                  <i class="fa fa-star"></i> <?php esc_html_e('Model:', 'retrotube'); ?> <?php echo esc_html(get_the_title()); ?>
                </span>
                <span class="video-meta-item video-meta-author">
                  <i class="fa fa-user"></i> <?php esc_html_e('From:', 'retrotube'); ?>
                  <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php echo esc_html(get_the_author()); ?></a>
                </span>
                <span class="video-meta-item video-meta-date">
                  <i class="fa fa-calendar"></i> <?php esc_html_e('Date:', 'retrotube'); ?> <?php echo esc_html(get_the_date()); ?>
                </span>
              </div>
              <div class="clear"></div>
            </header>

            <div class="entry-content">
              <?php if (function_exists('xbox_get_field_value') && (xbox_get_field_value('wpst-options', 'enable-views-system') == 'on' || xbox_get_field_value('wpst-options', 'enable-rating-system') == 'on')) : ?>
                <div id="rating-col">
                  <?php if (xbox_get_field_value('wpst-options', 'enable-views-system') == 'on') : ?>
                    <div id="video-views"><span>0</span> <?php esc_html_e('views', 'retrotube'); ?></div>
                  <?php endif; ?>
                  <?php if (xbox_get_field_value('wpst-options', 'enable-rating-system') == 'on') : ?>
                    <div class="rating-bar"><div class="rating-bar-meter"></div></div>
                    <div class="rating-result">
                      <div class="percentage">0%</div>
                      <div class="likes">
                        <i class="fa fa-thumbs-up"></i> <span class="likes_count">0</span>
                        <i class="fa fa-thumbs-down fa-flip-horizontal"></i> <span class="dislikes_count">0</span>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="tab-content">
                <?php
                $width = 100;
                if (function_exists('xbox_get_field_value') && (xbox_get_field_value('wpst-options', 'enable-views-system') == 'on' || xbox_get_field_value('wpst-options', 'enable-rating-system') == 'on')) {
                    $width = 70;
                }
                ?>
                <div id="video-about" class="width<?php echo (int) $width; ?>">
                  <div class="video-description">
                    <?php
                    $bio = function_exists('get_field') ? get_field('model') : '';
                    if ($bio) {
                        echo wp_kses_post(wpautop($bio));
                    } else {
                        the_content();
                    }

                    $flipbox_shortcode = function_exists('get_field') ? get_field('flipbox_shortcode') : '';
                    if ($flipbox_shortcode) {
                        echo do_shortcode($flipbox_shortcode);
                    }
                    ?>
                  </div>

                  <?php if (function_exists('xbox_get_field_value') && (xbox_get_field_value('wpst-options', 'show-categories-video-about') == 'on' || xbox_get_field_value('wpst-options', 'show-tags-video-about') == 'on')) : ?>
                    <div class="tags"><?php wpst_entry_footer(); ?></div>
                  <?php endif; ?>
                </div>

                <?php if (function_exists('xbox_get_field_value') && xbox_get_field_value('wpst-options', 'enable-video-share') == 'on') : ?>
                  <?php get_template_part('template-parts/content', 'share-buttons'); ?>
                <?php endif; ?>
              </div>
            </div><!-- .entry-content -->

            <?php if (function_exists('xbox_get_field_value') && xbox_get_field_value('wpst-options', 'display-related-videos') == 'on') : ?>
              <?php get_template_part('template-parts/content', 'related'); ?>
            <?php endif; ?>

            <?php
            if (function_exists('xbox_get_field_value') && xbox_get_field_value('wpst-options', 'enable-comments') == 'on') {
                if (comments_open() || get_comments_number()) {
                    comments_template();
                }
            }
            ?>
          </article>
          <?php error_log('[ModelLayout] Cloned single-video.php loaded for ' . get_the_title()); ?>
        <?php endwhile; ?>
      <?php endif; ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php get_footer(); ?>
