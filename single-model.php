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
          $post_id      = get_the_ID();
          $raw_title    = get_the_title();
          $display_title = trim(preg_replace('/★+/u', '★', $raw_title));
          if ($display_title === '') {
              $display_title = $raw_title;
          }

          $bio           = function_exists('get_field') ? get_field('bio') : '';
          $banner_field  = function_exists('get_field') ? get_field('banner_image') : '';
          $live_link_raw = function_exists('get_field') ? get_field('model_link') : '';

          $live_link_url = '';
          if (is_array($live_link_raw)) {
              if (!empty($live_link_raw['url']) && is_string($live_link_raw['url'])) {
                  $live_link_url = $live_link_raw['url'];
              }
          } elseif (is_string($live_link_raw)) {
              $live_link_url = $live_link_raw;
          }
          $live_link_url = $live_link_url ? esc_url_raw($live_link_url) : '';

          $hero_src = '';
          $hero_alt = '';
          if (is_array($banner_field)) {
              if (!empty($banner_field['ID'])) {
                  $hero_src = wp_get_attachment_image_url((int) $banner_field['ID'], 'full');
                  $hero_alt = get_post_meta((int) $banner_field['ID'], '_wp_attachment_image_alt', true);
              }
              if (!$hero_src && !empty($banner_field['url']) && is_string($banner_field['url'])) {
                  $hero_src = $banner_field['url'];
              }
              if (!$hero_alt && !empty($banner_field['alt']) && is_string($banner_field['alt'])) {
                  $hero_alt = $banner_field['alt'];
              }
          } elseif (is_string($banner_field) && $banner_field !== '') {
              $hero_src = $banner_field;
          }

          if (!$hero_src && has_post_thumbnail()) {
              $hero_src = get_the_post_thumbnail_url($post_id, 'full');
              $hero_alt = get_post_meta(get_post_thumbnail_id($post_id), '_wp_attachment_image_alt', true);
          }

          if (!$hero_alt) {
              $hero_alt = $display_title;
          }

          $schema_description = '';
          if (is_string($bio) && trim($bio) !== '') {
              $schema_description = wp_trim_words(wp_strip_all_tags($bio), 60, '');
          } else {
              $schema_description = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 60, '');
          }

          $schema = [
              '@context' => 'https://schema.org',
              '@type'    => 'Person',
              'name'     => wp_strip_all_tags($display_title),
              'url'      => esc_url_raw(get_permalink()),
          ];

          if ($schema_description !== '') {
              $schema['description'] = $schema_description;
          }

          if ($hero_src) {
              $schema['image'] = esc_url_raw($hero_src);
          }

          $schema_json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          if ($schema_json) {
              echo '<script type="application/ld+json">' . $schema_json . '</script>';
          }

          $about_content = '';
          if (is_string($bio) && trim($bio) !== '') {
              $about_content = wp_kses_post($bio);
          } else {
              $about_content = apply_filters('the_content', get_the_content(null, false, $post_id));
          }

          $tags = get_the_terms($post_id, 'post_tag');
          if (is_wp_error($tags)) {
              $tags = [];
          }

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

          $model_slug = get_post_field('post_name', $post_id);

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

          $name_meta_keys = apply_filters('tmw_model_video_name_meta_keys', ['model_name', 'model', 'models', 'model_names']);
          $slug_meta_keys = apply_filters('tmw_model_video_slug_meta_keys', ['model_slug', 'models_slug', 'model_slugs']);

          $meta_clauses = [];
          if (!empty($name_meta_keys) && is_array($name_meta_keys) && $display_title !== '') {
              foreach ($name_meta_keys as $meta_key) {
                  if (!is_string($meta_key) || $meta_key === '') {
                      continue;
                  }

                  $meta_clauses[] = [
                      'key'     => $meta_key,
                      'value'   => $display_title,
                      'compare' => 'LIKE',
                  ];
              }
          }

          if (!empty($slug_meta_keys) && is_array($slug_meta_keys) && $model_slug !== '') {
              foreach ($slug_meta_keys as $meta_key) {
                  if (!is_string($meta_key) || $meta_key === '') {
                      continue;
                  }

                  $meta_clauses[] = [
                      'key'     => $meta_key,
                      'value'   => $model_slug,
                      'compare' => 'LIKE',
                  ];
              }
          }

          $video_args = [
              'post_type'           => $video_post_types,
              'posts_per_page'      => 8,
              'orderby'             => 'date',
              'order'               => 'DESC',
              'no_found_rows'       => true,
              'ignore_sticky_posts' => true,
          ];

          if (!empty($model_term_ids)) {
              $video_args['tax_query'] = [
                  [
                      'taxonomy' => 'models',
                      'field'    => 'term_id',
                      'terms'    => $model_term_ids,
                  ],
              ];
          }

          if (!empty($meta_clauses)) {
              $video_args['meta_query'] = array_merge(['relation' => 'OR'], $meta_clauses);
          }

          if (empty($model_term_ids) && empty($meta_clauses)) {
              $video_args['meta_query'] = [
                  [
                      'key'     => 'model_name',
                      'value'   => $display_title,
                      'compare' => 'LIKE',
                  ],
              ];
          }

          $video_args   = apply_filters('tmw_model_videos_query_args', $video_args, $model_term_ids, $post_id);
          $model_videos = null;

          if (!empty($video_args['tax_query']) || !empty($video_args['meta_query'])) {
              $model_videos = new WP_Query($video_args);
          }
          ?>

          <article <?php post_class('single-model__article'); ?>>
            <header class="tmw-model-hero">
              <?php if ($display_title !== '') : ?>
                <h2 class="tmw-model-hero__title text-center"><?php echo esc_html($display_title); ?></h2>
              <?php endif; ?>
              <?php if ($hero_src) : ?>
                <figure class="tmw-model-hero__figure">
                  <img class="tmw-model-hero__image" src="<?php echo esc_url($hero_src); ?>" alt="<?php echo esc_attr($hero_alt); ?>" loading="lazy">
                </figure>
              <?php endif; ?>
            </header>

            <div class="title-block box-shadow">
              <h1 class="entry-title"><?php echo esc_html($display_title); ?></h1>
            </div>

            <div class="video-meta-inline"></div>

            <div id="video-tabs" class="tabs">
              <button class="tab-link active about" data-tab-id="tab-about">
                <i class="fa fa-info-circle"></i> <?php esc_html_e('About', 'retrotube-child'); ?>
              </button>
              <button class="tab-link tags" data-tab-id="tab-tags">
                <i class="fa fa-tags"></i> <?php esc_html_e('Tags', 'retrotube-child'); ?>
              </button>
              <button class="tab-link videos" data-tab-id="tab-videos">
                <i class="fa fa-film"></i> <?php esc_html_e('Model Videos', 'retrotube-child'); ?>
              </button>
            </div>

            <div class="entry-content tab-content">
              <div id="tab-about" class="tab-pane active">
                <div class="tmw-model-about">
                  <?php echo $about_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <?php if ($live_link_url) : ?>
                  <div class="model-live-link">
                    <a class="button model-live-link__button" href="<?php echo esc_url($live_link_url); ?>" target="_blank" rel="noopener noreferrer nofollow">
                      <?php esc_html_e('Visit Live Show', 'retrotube-child'); ?>
                    </a>
                  </div>
                <?php endif; ?>
              </div>

              <div id="tab-tags" class="tab-pane">
                <?php if (!empty($tags)) : ?>
                  <div class="tags">
                    <ul class="tags-list">
                      <?php foreach ($tags as $tag) :
                          $tag_link = get_term_link($tag);
                          if (is_wp_error($tag_link)) {
                              continue;
                          }
                          ?>
                          <li class="tags-list__item">
                            <a href="<?php echo esc_url($tag_link); ?>"><?php echo esc_html($tag->name); ?></a>
                          </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php else : ?>
                  <p class="tags-list__empty"><?php esc_html_e('No tags available.', 'retrotube-child'); ?></p>
                <?php endif; ?>
              </div>

              <div id="tab-videos" class="tab-pane">
                <?php if ($model_videos instanceof WP_Query && $model_videos->have_posts()) : ?>
                  <div class="box box-related box-shadow">
                    <div class="box-title"><span><?php esc_html_e('Model Videos', 'retrotube-child'); ?></span></div>
                    <div class="videos">
                      <?php while ($model_videos->have_posts()) : $model_videos->the_post(); ?>
                        <?php $video_title = get_the_title(); ?>
                        <article <?php post_class('video'); ?>>
                          <a class="video-thumb" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                            <div class="video-image">
                              <?php
                              if (has_post_thumbnail()) {
                                  echo get_the_post_thumbnail(get_the_ID(), 'medium_large', [
                                      'loading' => 'lazy',
                                      'alt'     => esc_attr($video_title),
                                  ]);
                              } else {
                                  $placeholder = function_exists('tmw_placeholder_image_url') ? tmw_placeholder_image_url() : '';
                                  if ($placeholder) {
                                      echo '<img src="' . esc_url($placeholder) . '" alt="' . esc_attr($video_title) . '" loading="lazy">';
                                  }
                              }
                              ?>
                            </div>
                            <h3 class="video-title"><?php echo esc_html($video_title); ?></h3>
                          </a>
                        </article>
                      <?php endwhile; ?>
                    </div>
                  </div>
                <?php else : ?>
                  <p class="model-videos__empty"><?php esc_html_e('No videos found for this model yet.', 'retrotube-child'); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($model_videos instanceof WP_Query) : ?>
              <?php wp_reset_postdata(); ?>
            <?php endif; ?>

            <?php if (comments_open() || get_comments_number()) : ?>
              <?php comments_template(); ?>
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
