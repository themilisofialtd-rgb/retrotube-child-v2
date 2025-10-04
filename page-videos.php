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
        global $wp_widget_factory;

        $videos_widget_class = '';
        $registered_widgets  = array();

        if ( is_object( $wp_widget_factory ) && isset( $wp_widget_factory->widgets ) ) {
            $registered_widgets = $wp_widget_factory->widgets;
        }

        if ( isset( $registered_widgets['RetroTube_Videos_Block_Widget'] ) ) {
            $videos_widget_class = 'RetroTube_Videos_Block_Widget';
        } elseif ( isset( $registered_widgets['Retrotube_Videos_Block_Widget'] ) ) {
            $videos_widget_class = 'Retrotube_Videos_Block_Widget';
        } elseif ( isset( $registered_widgets['Video_Block_Widget'] ) ) {
            $videos_widget_class = 'Video_Block_Widget';
        }

        $videos_widget_args = array(
            array(
                'title' => __( 'Videos being watched', 'retrotube' ),
                'orderby' => 'rand',
                'posts_per_page' => 12,
                'columns' => 4,
            ),
            array(
                'title' => __( 'Latest videos', 'retrotube' ),
                'orderby' => 'date',
                'posts_per_page' => 12,
                'columns' => 4,
            ),
            array(
                'title' => __( 'Longest videos', 'retrotube' ),
                'orderby' => 'duration',
                'posts_per_page' => 12,
                'columns' => 4,
            ),
        );

        $videos_widget_display_args = array(
            'before_widget' => '<div class="widget videos-block">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        );

        foreach ( $videos_widget_args as $widget_args ) {
            if ( $videos_widget_class ) {
                the_widget( $videos_widget_class, $widget_args, $videos_widget_display_args );
                continue;
            }

            if ( function_exists( 'widget_videos_block' ) ) {
                widget_videos_block( $widget_args );
            }
        }
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
