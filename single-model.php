<?php
/**
 * Template for displaying single Model posts.
 */

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right single-model">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/breadcrumbs'); ?>
          <?php
          if (locate_template('template-parts/single-model_bio.php', false, false)) {
              get_template_part('template-parts/single-model_bio');
          } else {
              get_template_part('single-model_bio');
          }

          $schema_name        = get_the_title();
          $schema_url         = get_permalink();
          $schema_description = '';
          $schema_image       = '';

          if (function_exists('get_field')) {
              $bio_field = get_field('bio');
              if (is_string($bio_field) && trim($bio_field) !== '') {
                  $schema_description = wp_trim_words(wp_strip_all_tags($bio_field), 60, '');
              }

              $banner = get_field('banner_image');
              if (is_array($banner) && !empty($banner['url'])) {
                  $schema_image = esc_url_raw($banner['url']);
              } elseif (is_string($banner) && $banner !== '') {
                  $schema_image = esc_url_raw($banner);
              }
          }

          if ($schema_description === '') {
              $schema_description = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 60, '');
          }

          if ($schema_image === '' && has_post_thumbnail()) {
              $thumb_url = get_the_post_thumbnail_url(null, 'full');
              if ($thumb_url) {
                  $schema_image = esc_url_raw($thumb_url);
              }
          }

          $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => wp_strip_all_tags($schema_name),
            'url'         => esc_url_raw($schema_url),
          ];

          if ($schema_description !== '') {
              $schema['description'] = $schema_description;
          }

          if ($schema_image !== '') {
              $schema['image'] = $schema_image;
          }

          $schema_json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          if ($schema_json) {
              echo '<script type="application/ld+json">' . $schema_json . '</script>';
          }
          ?>

          <div class="entry-content">
            <?php the_content(); ?>
          </div>

          <?php
          $post_id        = get_the_ID();
          $model_term_ids = wp_get_post_terms($post_id, 'models', ['fields' => 'ids']);

          if (is_wp_error($model_term_ids) || empty($model_term_ids)) {
              $model_term_ids = [];
          }

          if (empty($model_term_ids)) {
              $mapped_terms = get_terms([
                'taxonomy'   => 'models',
                'hide_empty' => false,
                'fields'     => 'ids',
                'meta_query' => [
                  [
                    'key'   => 'tmw_model_post_id',
                    'value' => $post_id,
                  ],
                ],
              ]);

              if (!is_wp_error($mapped_terms) && !empty($mapped_terms)) {
                  $model_term_ids = array_map('intval', $mapped_terms);
              }
          }

          if (empty($model_term_ids)) {
              $slug_term = get_term_by('slug', get_post_field('post_name', $post_id), 'models');
              if ($slug_term && !is_wp_error($slug_term)) {
                  $model_term_ids[] = (int) $slug_term->term_id;
              }
          }

          $model_term_ids = array_values(array_unique(array_filter(array_map('intval', $model_term_ids))));

          if (!empty($model_term_ids)) {
              $default_video_types = ['video', 'videos', 'wpsc-video', 'wp-script-video', 'wpws_video', 'post'];
              $video_post_types    = apply_filters('tmw_model_video_post_types', $default_video_types);

              if (empty($video_post_types) || !is_array($video_post_types)) {
                  $video_post_types = $default_video_types;
              }

              $video_post_types = array_values(array_unique(array_filter(array_map(
                  static function ($type) {
                      if (!is_string($type)) {
                          return '';
                      }

                      $sanitized = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $type));

                      return $sanitized !== '' ? $sanitized : '';
                  },
                  $video_post_types
              ))));

              if (empty($video_post_types)) {
                  $video_post_types = $default_video_types;
              }

              $video_args = [
                'post_type'           => $video_post_types,
                'posts_per_page'      => 8,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
                'tax_query'           => [
                  [
                    'taxonomy' => 'models',
                    'field'    => 'term_id',
                    'terms'    => $model_term_ids,
                  ],
                ],
              ];

              $video_args = apply_filters('tmw_model_videos_query_args', $video_args, $model_term_ids, $post_id);
              $model_videos = new WP_Query($video_args);

              if ($model_videos->have_posts()) :
                  $videos_heading_id = 'model-videos-' . get_post_field('post_name', $post_id);
                  ?>
                  <section class="model-videos" aria-labelledby="<?php echo esc_attr($videos_heading_id); ?>">
                    <header class="model-videos__header">
                      <h2 id="<?php echo esc_attr($videos_heading_id); ?>">
                        <?php printf(
                          /* translators: %s is the model name */
                          esc_html__('Model %s Videos', 'retrotube-child'),
                          esc_html($schema_name)
                        ); ?>
                      </h2>
                    </header>
                    <div class="model-videos__grid">
                      <?php
                      while ($model_videos->have_posts()) :
                          $model_videos->the_post();
                          $video_title = get_the_title();
                          ?>
                          <article <?php post_class('model-videos__item'); ?>>
                            <a class="model-videos__link" href="<?php the_permalink(); ?>">
                              <div class="model-videos__thumb">
                                <?php
                                if (has_post_thumbnail()) {
                                    echo get_the_post_thumbnail(get_the_ID(), 'medium_large', [
                                      'loading' => 'lazy',
                                      'class'   => 'model-videos__image',
                                      'alt'     => esc_attr($video_title),
                                    ]);
                                } else {
                                    $placeholder = function_exists('tmw_placeholder_image_url') ? tmw_placeholder_image_url() : '';
                                    if ($placeholder) {
                                        echo '<img class="model-videos__image" src="' . esc_url($placeholder) . '" alt="' . esc_attr($video_title) . '" loading="lazy">';
                                    }
                                }
                                ?>
                              </div>
                              <h3 class="model-videos__title"><?php echo esc_html($video_title); ?></h3>
                            </a>
                          </article>
                      <?php endwhile; ?>
                    </div>
                  </section>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
          <?php }
          ?>

          <?php comments_template(); ?>
        <?php endwhile; ?>
      <?php endif; ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php get_footer(); ?>
