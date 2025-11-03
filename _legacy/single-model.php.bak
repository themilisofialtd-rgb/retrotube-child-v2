<?php
/**
 * Single template for the Models CPT using the parent video layout structure.
 */

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right single-model">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php error_log('[ModelPage] single-model.php loaded for ' . get_the_title()); ?>
          <?php get_template_part('template-parts/breadcrumbs'); ?>
          <?php
          $model_name    = get_the_title();
          $portrait_html = '';
          $portrait_url  = '';

          if (function_exists('get_field')) {
            $portrait_field = get_field('model_portrait');
            if (is_array($portrait_field)) {
              if (!empty($portrait_field['ID'])) {
                $portrait_html = wp_get_attachment_image((int) $portrait_field['ID'], 'large', false, [
                  'class' => 'model-portrait-image',
                  'alt'   => get_the_title(),
                ]);
              } elseif (!empty($portrait_field['url'])) {
                $portrait_url = $portrait_field['url'];
              }
            } elseif (is_numeric($portrait_field)) {
              $portrait_html = wp_get_attachment_image((int) $portrait_field, 'large', false, [
                'class' => 'model-portrait-image',
                'alt'   => get_the_title(),
              ]);
            } elseif (is_string($portrait_field) && $portrait_field !== '') {
              $portrait_url = $portrait_field;
            }
          }

          if (!$portrait_html && !$portrait_url && has_post_thumbnail()) {
            $portrait_html = get_the_post_thumbnail(get_the_ID(), 'large', [
              'class' => 'model-portrait-image',
            ]);
          }

          if (!$portrait_html && $portrait_url) {
            $portrait_html = sprintf(
              '<img src="%s" alt="%s" class="model-portrait-image" />',
              esc_url($portrait_url),
              esc_attr(get_the_title())
            );
          }

          $social_fields = [
            'link_instagram' => ['label' => 'Instagram',   'icon' => 'fa-instagram'],
            'link_twitter'   => ['label' => 'Twitter/X',   'icon' => 'fa-twitter'],
            'link_tiktok'    => ['label' => 'TikTok',      'icon' => 'fa-music'],
            'link_onlyfans'  => ['label' => 'OnlyFans',    'icon' => 'fa-star'],
            'link_fancentro' => ['label' => 'FanCentro',   'icon' => 'fa-play-circle'],
            'link_mymfans'   => ['label' => 'MyM.fans',    'icon' => 'fa-heart'],
          ];

          $social_links = [];

          if (function_exists('get_field')) {
            foreach ($social_fields as $field_key => $meta) {
              $field_value = get_field($field_key);

              if (is_array($field_value) && isset($field_value['url'])) {
                $field_value = $field_value['url'];
              }

              if (is_string($field_value)) {
                $field_value = trim($field_value);
              }

              if ($field_value) {
                $social_links[] = [
                  'url'   => (string) $field_value,
                  'label' => $meta['label'],
                  'icon'  => $meta['icon'],
                ];
              }
            }
          }

          $model_videos_args = [
            'post_type'           => 'video',
            'posts_per_page'      => 6,
            's'                   => $model_name,
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
          ];

          $model_videos_query = new WP_Query($model_videos_args);
          $item_list_elements = [];
          $model_videos_html  = '';

          if ($model_videos_query->have_posts()) {
            foreach ($model_videos_query->posts as $index => $video_post) {
              $video_title = get_the_title($video_post);
              $video_url   = get_permalink($video_post);
              $video_url   = $video_url ? esc_url_raw($video_url) : '';

              if ($video_url) {
                $item_list_elements[] = [
                  '@type'    => 'ListItem',
                  'position' => $index + 1,
                  'name'     => wp_strip_all_tags($video_title),
                  'url'      => $video_url,
                ];
              }
            }

            $model_videos_query->rewind_posts();

            global $wp_query, $post;

            $previous_wp_query = $wp_query;
            $previous_post     = $post;

            $wp_query = $model_videos_query;
            if (!empty($model_videos_query->post)) {
              $post = $model_videos_query->post;
            }

            ob_start();
            get_template_part('template-parts/content', 'related');
            $related_markup = ob_get_clean();

            $wp_query = $previous_wp_query;
            $post     = $previous_post;

            if (!empty($related_markup)) {
              $model_videos_html = str_ireplace(
                ['Related videos', 'Show more videos'],
                ['Model Videos', 'Show more model videos'],
                $related_markup
              );
            }

            wp_reset_postdata();
          }
          ?>

          <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
              <?php if ($portrait_html) : ?>
                <div class="video-player box-shadow">
                  <?php echo $portrait_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
              <?php endif; ?>

              <div class="title-block box-shadow">
                <?php the_title('<h1 class="entry-title" itemprop="name">', '</h1>'); ?>
                <div id="video-tabs" class="tabs">
                  <button class="tab-link active about" data-tab-id="video-about">
                    <i class="fa fa-info-circle"></i> <?php esc_html_e('About', 'wpst'); ?>
                  </button>
                </div>
              </div>

              <div class="clear"></div>
            </header>

            <div class="entry-content">
              <div class="tab-content">
                <div id="video-about" class="width100">
                  <div class="video-description">
                    <div class="desc">
                      <?php the_content(); ?>
                    </div>
                  </div>

                  <?php if (!empty($social_links)) : ?>
                    <div class="model-social-links">
                      <ul class="social-icons">
                        <?php foreach ($social_links as $social_link) : ?>
                          <li>
                            <a href="<?php echo esc_url($social_link['url']); ?>" target="_blank" rel="nofollow noopener">
                              <i class="fa <?php echo esc_attr($social_link['icon']); ?>"></i>
                              <span class="screen-reader-text"><?php echo esc_html($social_link['label']); ?></span>
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>

                  <?php if (has_tag()) : ?>
                    <div class="video-tags"><?php the_tags('', ' ', ''); ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <?php if ($model_videos_html) : ?>
              <?php echo $model_videos_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>

            <?php comments_template(); ?>

            <?php
            $person_name        = esc_js(get_the_title());
            $person_image       = esc_url(get_the_post_thumbnail_url() ?: '');
            $person_description = esc_js(wp_strip_all_tags(get_the_excerpt()));
            $person_url         = esc_url(get_permalink());
            ?>
            <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "<?php echo $person_name; ?>",
  "image": "<?php echo $person_image; ?>",
  "description": "<?php echo $person_description; ?>",
  "url": "<?php echo $person_url; ?>"
}
            </script>
            <?php if (!empty($item_list_elements)) :
              $item_list_schema = [
                '@context'        => 'https://schema.org',
                '@type'           => 'ItemList',
                'name'            => wp_strip_all_tags($model_name) . ' Videos',
                'itemListElement' => $item_list_elements,
              ];
              ?>
              <script type="application/ld+json">
                <?php echo wp_json_encode($item_list_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
              </script>
            <?php endif; ?>
          </article>
        <?php endwhile; ?>
      <?php endif; ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php get_footer(); ?>
