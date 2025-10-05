<?php
/**
 * Template for single model pages
 * Based on page-videos.php layout
 */

get_header(); ?>

<div id="primary" class="content-area with-sidebar-right">
    <main id="main" class="site-main with-sidebar-right" role="main">

        <?php while ( have_posts() ) : the_post(); ?>

            <div class="entry-content">
                <?php
                $portrait = function_exists('get_field') ? get_field('model_portrait') : '';
                if (is_array($portrait) && !empty($portrait['url'])) {
                    $portrait_url = $portrait['url'];
                } else {
                    $portrait_url = $portrait ? $portrait : get_the_post_thumbnail_url(get_the_ID(), 'large');
                }
                if ($portrait_url):
                ?>
                <div class="video-player box-shadow">
                    <img src="<?php echo esc_url($portrait_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
                <?php endif; ?>

                <div class="title-block box-shadow">
                    <div class="video-meta-inline">
                        <h1 class="entry-title"><i class="fa fa-user"></i> <?php the_title(); ?></h1>
                    </div>
                </div>

                <div id="video-tabs" class="tabs">
                    <div class="tab-content">
                        <div id="video-about" class="tab-pane">
                            <div class="video-description">
                                <div class="desc">
                                    <?php the_content(); ?>

                                    <?php
                                    if ( function_exists('get_field') ) :
                                        $links = [
                                            'instagram_url' => 'fa-instagram',
                                            'twitter_url'   => 'fa-twitter',
                                            'tiktok_url'    => 'fa-music',
                                            'onlyfans_url'  => 'fa-star',
                                            'fancentro_url' => 'fa-plus',
                                            'mymfans_url'   => 'fa-heart'
                                        ];
                                        $has_links = false;
                                        ob_start();
                                        echo '<div class="model-social-links">';
                                        foreach ( $links as $k => $icon ) {
                                            $url = get_field($k);
                                            if ( $url ) {
                                                $has_links = true;
                                                echo '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener"><i class="fa ' . esc_attr($icon) . '"></i></a> ';
                                            }
                                        }
                                        echo '</div>';
                                        $html = ob_get_clean();
                                        if ( $has_links ) echo $html;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- .entry-content -->

            <?php
            $model_name = get_the_title();
            $model_videos = new WP_Query([
                'post_type' => 'video',
                'posts_per_page' => 6,
                's' => $model_name,
                'no_found_rows' => true,
                'ignore_sticky_posts' => true,
            ]);

            if ( $model_videos->have_posts() ) :
            ?>
            <section class="related-videos box-shadow">
                <h3 class="widget-title"><i class="fa fa-video-camera"></i> Model Videos</h3>
                <div class="video-loop">
                    <?php while ( $model_videos->have_posts() ) : $model_videos->the_post(); ?>
                        <?php get_template_part('template-parts/loop', 'video'); ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>

            <div class="video-tags"><?php the_tags('', ' ', ''); ?></div>
            <?php comments_template(); ?>

        <?php endwhile; ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>

<?php error_log('[ModelPage] single-model.php loaded for ' . get_the_title()); ?>
