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

        <?php
        // Use RetroTube's own homepage blocks
        if ( function_exists( 'widget_videos_block' ) ) {
            // Videos being watched
            widget_videos_block( array(
                'title' => __( 'Videos being watched', 'retrotube' ),
                'orderby' => 'rand',
                'posts_per_page' => 12,
                'columns' => 4,
            ) );

            // Latest videos
            widget_videos_block( array(
                'title' => __( 'Latest videos', 'retrotube' ),
                'orderby' => 'date',
                'posts_per_page' => 12,
                'columns' => 4,
            ) );

            // Longest videos
            widget_videos_block( array(
                'title' => __( 'Longest videos', 'retrotube' ),
                'orderby' => 'duration',
                'posts_per_page' => 12,
                'columns' => 4,
            ) );
        }
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
