<?php
/**
 * Template for single model pages that mirrors the RetroTube video layout.
 */

get_header();
?>
<div id="content" class="site-content row">
    <div id="primary" class="content-area with-sidebar-right single-model">
        <main id="main" class="site-main with-sidebar-right" role="main">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php error_log('[ModelPage] single-model.php loaded for ' . get_the_title()); ?>
                    <?php
                    $model_name = get_the_title();
                    $model_slug = get_post_field('post_name', get_the_ID());

                    $portrait_html = '';
                    $portrait_url  = '';

                    if (function_exists('get_field')) {
                        $portrait_field = get_field('model_portrait');

                        if (is_array($portrait_field)) {
                            if (!empty($portrait_field['ID'])) {
                                $portrait_html = wp_get_attachment_image((int) $portrait_field['ID'], 'large', false, [
                                    'class' => 'model-portrait-image',
                                    'alt'   => $model_name,
                                ]);
                            } elseif (!empty($portrait_field['url'])) {
                                $portrait_url = $portrait_field['url'];
                            }
                        } elseif (is_numeric($portrait_field)) {
                            $portrait_html = wp_get_attachment_image((int) $portrait_field, 'large', false, [
                                'class' => 'model-portrait-image',
                                'alt'   => $model_name,
                            ]);
                        } elseif (is_string($portrait_field) && $portrait_field !== '') {
                            $portrait_url = $portrait_field;
                        }
                    }

                    if (!$portrait_html && !$portrait_url && has_post_thumbnail()) {
                        $portrait_html = get_the_post_thumbnail(get_the_ID(), 'large', [
                            'class' => 'model-portrait-image',
                            'alt'   => $model_name,
                        ]);
                    }

                    if (!$portrait_html && $portrait_url) {
                        $portrait_html = sprintf(
                            '<img src="%s" alt="%s" class="model-portrait-image" />',
                            esc_url($portrait_url),
                            esc_attr($model_name)
                        );
                    }
                    ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class('video-page'); ?>>
                        <header class="entry-header">
                            <div class="video-player box-shadow">
                                <?php
                                if ($portrait_html) {
                                    echo $portrait_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                } elseif (has_post_thumbnail()) {
                                    the_post_thumbnail('large', [
                                        'class' => 'model-portrait-image',
                                        'alt'   => $model_name,
                                    ]);
                                }
                                ?>
                            </div>

                            <div class="title-block box-shadow">
                                <h1 class="entry-title"><?php the_title(); ?></h1>
                            </div>

                            <div class="video-meta-inline">
                                <span class="video-meta-item video-meta-author"><i class="fa fa-user"></i> <?php esc_html_e('Added by', 'wpst'); ?> <?php the_author_posts_link(); ?></span>
                                <span class="video-meta-item video-meta-date"><i class="fa fa-calendar"></i> <?php the_time(get_option('date_format')); ?></span>
                            </div>
                        </header>

                        <div class="clear"></div>

                        <?php error_log('[ModelPage] single-model.php fully aligned with RetroTube layout for ' . $model_name); ?>

                        <div class="entry-content">
                            <div id="video-tabs" class="tabs">
                                <button class="active" data-tab="about">About</button>
                                <button data-tab="comments">Comments</button>
                            </div>

                            <div id="rating-col"></div>

                            <div class="tab-content" id="video-about">
                                <?php the_content(); ?>
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

                        <?php comments_template(); ?>
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
