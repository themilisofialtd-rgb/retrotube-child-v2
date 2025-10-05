<?php
/**
 * Template for single model pages that mirrors the video layout.
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
                    $model_name   = get_the_title();
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

                    $social_fields = [
                        'instagram_url' => ['label' => 'Instagram', 'icon' => 'fa-instagram'],
                        'twitter_url'   => ['label' => 'Twitter/X', 'icon' => 'fa-twitter'],
                        'tiktok_url'    => ['label' => 'TikTok', 'icon' => 'fa-music'],
                        'onlyfans_url'  => ['label' => 'OnlyFans', 'icon' => 'fa-star'],
                        'fancentro_url' => ['label' => 'FanCentro', 'icon' => 'fa-plus'],
                        'mymfans_url'   => ['label' => 'MyM.fans', 'icon' => 'fa-heart'],
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

                            if (!empty($field_value)) {
                                $social_links[] = [
                                    'url'   => (string) $field_value,
                                    'label' => $meta['label'],
                                    'icon'  => $meta['icon'],
                                ];
                            }
                        }
                    }
                    ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class('model-profile'); ?>>
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

                            <div class="video-meta-inline">
                                <span class="video-meta-item video-meta-author">
                                    <i class="fa fa-user"></i>
                                    <?php
                                    $author_markup = sprintf(
                                        '<a href="%s">%s</a>',
                                        esc_url(get_author_posts_url(get_the_author_meta('ID'))),
                                        esc_html(get_the_author())
                                    );
                                    echo wp_kses_post(
                                        sprintf(
                                            /* translators: %s: model author name */
                                            __('Added by %s', 'wpst'),
                                            $author_markup
                                        )
                                    );
                                    ?>
                                </span>
                                <span class="video-meta-item video-meta-date">
                                    <i class="fa fa-calendar"></i>
                                    <?php
                                    printf(
                                        /* translators: %s: published date */
                                        esc_html__('Published %s', 'wpst'),
                                        esc_html(get_the_date())
                                    );
                                    ?>
                                </span>
                                <?php
                                $model_terms = get_the_terms(get_the_ID(), 'model-category');
                                if (!empty($model_terms) && !is_wp_error($model_terms)) :
                                    $term_links = [];
                                    foreach ($model_terms as $term) {
                                        $term_link = get_term_link($term);
                                        if (!is_wp_error($term_link)) {
                                            $term_links[] = sprintf('<a href="%s">%s</a>', esc_url($term_link), esc_html($term->name));
                                        }
                                    }
                                    if ($term_links) :
                                        ?>
                                        <span class="video-meta-item video-meta-model">
                                            <i class="fa fa-tags"></i>
                                            <?php
                                            echo wp_kses_post(
                                                sprintf(
                                                    /* translators: %s: comma separated list of model categories */
                                                    __('Categories: %s', 'wpst'),
                                                    implode(', ', $term_links)
                                                )
                                            );
                                            ?>
                                        </span>
                                        <?php
                                    endif;
                                endif;
                                ?>
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
                        </div><!-- .entry-content -->

                        <?php
                        $model_videos = new WP_Query([
                            'post_type'           => 'video',
                            'posts_per_page'      => 6,
                            's'                   => $model_name,
                            'no_found_rows'       => true,
                            'ignore_sticky_posts' => true,
                        ]);

                        if ($model_videos->have_posts()) :
                            ?>
                            <section class="related-videos box-shadow">
                                <h3 class="widget-title">
                                    <i class="fa fa-video-camera"></i>
                                    <?php
                                    printf(
                                        /* translators: %s: model name */
                                        esc_html__('Related videos by %s', 'wpst'),
                                        esc_html($model_name)
                                    );
                                    ?>
                                </h3>
                                <div class="video-loop">
                                    <?php
                                    while ($model_videos->have_posts()) :
                                        $model_videos->the_post();
                                        get_template_part('template-parts/loop', 'video');
                                    endwhile;
                                    ?>
                                </div>
                            </section>
                            <?php
                        endif;

                        wp_reset_postdata();
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
