<?php
/**
 * Single Model template cloned from the video layout.
 * Remove this file if layout breaks; WordPress will fall back to the parent template.
 */

get_header();
?>
<div id="content" class="site-content row">
    <div id="primary" class="content-area with-sidebar-right single-model">
        <main id="main" class="site-main with-sidebar-right" role="main">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php
                    $model_id   = get_the_ID();
                    $model_name = get_the_title();
                    $model_slug = get_post_field('post_name', $model_id);

                    error_log('[ModelPage] Bulletproof layout loaded for ' . $model_name);

                    $portrait_html  = '';
                    $portrait_field = function_exists('get_field') ? get_field('model_portrait') : null;

                    if (is_array($portrait_field) && !empty($portrait_field['ID'])) {
                        $portrait_html = wp_get_attachment_image(
                            (int) $portrait_field['ID'],
                            'large',
                            false,
                            [
                                'class' => 'model-portrait',
                                'alt'   => $model_name,
                            ]
                        );
                    } elseif (!empty($portrait_field) && is_numeric($portrait_field)) {
                        $portrait_html = wp_get_attachment_image(
                            (int) $portrait_field,
                            'large',
                            false,
                            [
                                'class' => 'model-portrait',
                                'alt'   => $model_name,
                            ]
                        );
                    } elseif (is_array($portrait_field) && !empty($portrait_field['url'])) {
                        $portrait_html = sprintf(
                            '<img src="%s" alt="%s" class="model-portrait" />',
                            esc_url($portrait_field['url']),
                            esc_attr($model_name)
                        );
                    } elseif (is_string($portrait_field) && $portrait_field !== '') {
                        $portrait_html = sprintf(
                            '<img src="%s" alt="%s" class="model-portrait" />',
                            esc_url($portrait_field),
                            esc_attr($model_name)
                        );
                    }

                    if (!$portrait_html && has_post_thumbnail()) {
                        $portrait_html = get_the_post_thumbnail(
                            $model_id,
                            'large',
                            [
                                'class' => 'model-portrait',
                                'alt'   => $model_name,
                            ]
                        );
                    }

                    if (!$portrait_html) {
                        $placeholder_file = get_stylesheet_directory() . '/images/placeholder-model.jpg';
                        $placeholder_url  = get_stylesheet_directory_uri() . '/images/placeholder-model.jpg';

                        if (!file_exists($placeholder_file)) {
                            $fallback_path = get_stylesheet_directory_uri() . '/assets/images/placeholder-model.jpg';
                            $placeholder_url = $fallback_path;
                        }

                        $portrait_html = sprintf(
                            '<img src="%s" alt="%s" class="model-portrait" />',
                            esc_url($placeholder_url),
                            esc_attr($model_name)
                        );
                    }

                    // Ensure portrait markup includes schema itemprop.
                    $portrait_markup = str_replace('<img ', '<img itemprop="image" ', $portrait_html);

                    $views_meta_key = 'views';
                    $views_count    = get_post_meta($model_id, $views_meta_key, true);
                    $views_display  = $views_count !== '' ? absint($views_count) : 1280;
                    ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class('video-page'); ?> itemscope itemtype="https://schema.org/Person">
                        <header class="entry-header">
                            <div class="video-player box-shadow">
                                <?php echo $portrait_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>

                            <div class="title-block box-shadow">
                                <?php the_title('<h1 class="entry-title" itemprop="name">', '</h1>'); ?>
                                <div id="rating" class="model-rating-disabled">
                                    <span id="video-rate"><i class="fa fa-user"></i> <?php esc_html_e('Model profile', 'wpst'); ?></span>
                                </div>
                                <div id="video-tabs" class="tabs">
                                    <button class="tab-link active about" data-tab-id="video-about">
                                        <i class="fa fa-info-circle"></i> <?php esc_html_e('About', 'wpst'); ?>
                                    </button>
                                    <button class="tab-link comments" data-tab-id="video-comments">
                                        <i class="fa fa-comments"></i> <?php esc_html_e('Comments', 'wpst'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="video-meta-inline">
                                <span class="video-meta-item video-meta-author"><i class="fa fa-user"></i> <?php esc_html_e('Added by', 'wpst'); ?> <?php the_author_posts_link(); ?></span>
                                <span class="video-meta-item video-meta-date"><i class="fa fa-calendar"></i> <?php echo esc_html(get_the_date()); ?></span>
                                <span class="video-meta-item video-meta-category"><i class="fa fa-folder-open"></i> <a href="<?php echo esc_url(home_url('/models/')); ?>"><?php esc_html_e('Models', 'wpst'); ?></a></span>
                                <span class="video-meta-item video-meta-views"><i class="fa fa-eye"></i> <?php echo esc_html(number_format_i18n($views_display)); ?> <?php esc_html_e('views', 'wpst'); ?></span>
                            </div>

                            <div class="clear"></div>
                        </header>

                        <div class="entry-content">
                            <div id="video-about" class="tab-content active" itemprop="description">
                                <?php the_content(); ?>

                                <?php if (has_tag()) : ?>
                                    <div class="video-tags"><?php the_tags('<span class="video-meta-item"><i class="fa fa-tags"></i> ', ', ', '</span>'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div id="video-comments" class="tab-content">
                                <?php comments_template(); ?>
                            </div>
                        </div>

                        <?php
                        if (!empty($model_slug)) {
                            $related_videos = new WP_Query([
                                'post_type'           => 'video',
                                'posts_per_page'      => 6,
                                'post_status'         => 'publish',
                                'ignore_sticky_posts' => true,
                                'tag'                 => $model_slug,
                            ]);

                            if ($related_videos->have_posts()) :
                                ?>
                                <section class="related-videos box-shadow">
                                    <h3 class="widget-title">
                                        <i class="fa fa-video-camera"></i>
                                        <?php
                                        printf(
                                            /* translators: %s: model name. */
                                            esc_html__('Related videos featuring %s', 'wpst'),
                                            esc_html($model_name)
                                        );
                                        ?>
                                    </h3>
                                    <div class="video-loop">
                                        <?php
                                        while ($related_videos->have_posts()) :
                                            $related_videos->the_post();
                                            get_template_part('template-parts/loop', 'video');
                                        endwhile;
                                        ?>
                                    </div>
                                </section>
                                <?php
                            endif;

                            wp_reset_postdata();
                        }
                        ?>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </main>
    </div>
    <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
        <?php get_sidebar(); ?>
    </aside>
</div>
<?php
get_footer();
