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

                    <article id="post-<?php the_ID(); ?>" <?php post_class('video-page'); ?> itemscope itemtype="https://schema.org/Person">
                        <header class="entry-header">
                            <div class="video-player box-shadow">
                                <?php
                                if ($portrait_html) {
                                    // Ensure portrait markup carries the required schema itemprop.
                                    $portrait_html = str_replace('<img ', '<img itemprop="image" ', $portrait_html);
                                    echo $portrait_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                } elseif (has_post_thumbnail()) {
                                    the_post_thumbnail('large', [
                                        'class'    => 'model-portrait-image',
                                        'alt'      => $model_name,
                                        'itemprop' => 'image',
                                    ]);
                                } else {
                                    echo '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/placeholder-model.jpg') . '" alt="' . esc_attr__('Model portrait', 'wpst') . '" class="model-portrait-image" itemprop="image" />';
                                }
                                ?>
                            </div>

                            <div class="title-block box-shadow">
                                <h1 class="entry-title" itemprop="name"><?php the_title(); ?></h1>
                            </div>

                            <div class="video-meta-inline">
                                <span class="video-meta-item video-meta-author"><i class="fa fa-user"></i> <?php esc_html_e('Added by', 'wpst'); ?> <?php the_author_posts_link(); ?></span>
                                <span class="video-meta-item video-meta-date"><i class="fa fa-calendar"></i> <?php echo esc_html(get_the_date()); ?></span>
                                <span class="video-meta-item video-meta-category"><i class="fa fa-folder-open"></i> <a href="<?php echo esc_url(home_url('/models/')); ?>"><?php esc_html_e('Models', 'wpst'); ?></a></span>
                                <?php
                                $views_meta_key = 'views';
                                $views_count    = get_post_meta(get_the_ID(), $views_meta_key, true);
                                $views_display  = $views_count !== '' ? absint($views_count) : 1280;
                                ?>
                                <span class="video-meta-item video-meta-views"><i class="fa fa-eye"></i> <?php echo esc_html(number_format_i18n($views_display)); ?> <?php esc_html_e('views', 'wpst'); ?></span>
                            </div>
                        </header>

                        <div class="clear"></div>

                        <?php error_log('[ModelPage] single-model.php fully aligned with RetroTube layout for ' . $model_name); ?>

                        <div class="entry-content">
                            <div id="video-tabs" class="tabs">
                                <button class="active" data-tab="about">About</button>
                                <button data-tab="comments">Comments</button>
                            </div>
                            <div id="rating-col">
                                <span class="like-btn"><i class="fa fa-thumbs-up"></i></span>
                                <span class="dislike-btn"><i class="fa fa-thumbs-down"></i></span>
                            </div>
                            <div class="tab-content" id="video-about" itemprop="description">
                                <?php the_content(); ?>
                                <div class="video-tags"><?php the_tags('<span class="video-meta-item"><i class="fa fa-tags"></i> ', ', ', '</span>'); ?></div>
                                <?php
                                $social_fields = [
                                    'instagram' => 'fa-instagram',
                                    'twitter'   => 'fa-twitter',
                                    'onlyfans'  => 'fa-heart',
                                ];
                                $social_links = [];

                                foreach ($social_fields as $field_key => $icon_class) {
                                    $field_value = '';

                                    if (function_exists('get_field')) {
                                        $field_value = (string) get_field($field_key);
                                    }

                                    if ($field_value === '') {
                                        $field_value = (string) get_post_meta(get_the_ID(), $field_key, true);
                                    }

                                    if ($field_value !== '') {
                                        $social_links[$field_key] = [
                                            'url'  => esc_url_raw($field_value),
                                            'icon' => $icon_class,
                                        ];
                                    }
                                }

                                if (!empty($social_links)) :
                                    ?>
                                    <div class="model-follow-bar box-shadow">
                                        <span class="follow-label"><?php esc_html_e('Follow', 'wpst'); ?>:</span>
                                        <?php foreach ($social_links as $key => $data) : ?>
                                            <a class="follow-link follow-<?php echo esc_attr($key); ?>" href="<?php echo esc_url($data['url']); ?>" target="_blank" rel="noopener">
                                                <i class="fa <?php echo esc_attr($data['icon']); ?>"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php
                                endif;
                                ?>
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
