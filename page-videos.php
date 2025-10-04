<?php
/**
 * Template Name: Videos Page
 */
get_header(); ?>

<div id="primary" class="content-area with-sidebar-right">
    <main id="main" class="site-main with-sidebar-right" role="main">

        <header class="entry-header">
            <h1 class="entry-title"><i class="fa fa-video-camera"></i> Videos</h1>
        </header>

        <?php if ( is_page( 'videos' ) ) : ?>

            <?php
            the_widget(
                'wpst_WP_Widget_Videos_Block',
                array(
                    'title'          => 'Videos being watched',
                    'video_type'     => 'random',
                    'video_number'   => 8,
                    'video_category' => 0,
                ),
                array(
                    'before_widget' => '<section class="widget widget_videos_block">',
                    'after_widget'  => '</section>',
                    'before_title'  => '<h2 class="widget-title">',
                    'after_title'   => '</h2>',
                )
            );

            the_widget(
                'wpst_WP_Widget_Videos_Block',
                array(
                    'title'          => 'Latest videos',
                    'video_type'     => 'latest',
                    'video_number'   => 6,
                    'video_category' => 0,
                ),
                array(
                    'before_widget' => '<section class="widget widget_videos_block">',
                    'after_widget'  => '</section>',
                    'before_title'  => '<h2 class="widget-title">',
                    'after_title'   => '</h2>',
                )
            );

            the_widget(
                'wpst_WP_Widget_Videos_Block',
                array(
                    'title'          => 'Longest videos',
                    'video_type'     => 'longest',
                    'video_number'   => 12,
                    'video_category' => 0,
                ),
                array(
                    'before_widget' => '<section class="widget widget_videos_block">',
                    'after_widget'  => '</section>',
                    'before_title'  => '<h2 class="widget-title">',
                    'after_title'   => '</h2>',
                )
            );
            ?>

        <?php endif; ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
