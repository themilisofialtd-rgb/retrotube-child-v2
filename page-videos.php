<?php
/**
 * Template Name: Videos Page
 */
get_header(); ?>

<div id="primary" class="content-area with-sidebar-right">
    <main id="main" class="site-main with-sidebar-right" role="main">

        <?php
        $filter       = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : '';
        $filter_query = null;

        if ( $filter ) {
            $query_args = array(
                'post_type'           => 'video',
                'post_status'         => 'publish',
                'posts_per_page'      => max( 1, (int) get_option( 'posts_per_page', 12 ) ),
                'ignore_sticky_posts' => true,
            );

            switch ( $filter ) {
                case 'latest':
                    $query_args['orderby'] = 'date';
                    $query_args['order']   = 'DESC';
                    break;

                case 'random':
                    $query_args['orderby'] = 'rand';
                    break;

                case 'longest':
                    $query_args['meta_key'] = '_duration';
                    $query_args['orderby']  = 'meta_value_num';
                    $query_args['order']    = 'DESC';
                    $query_args['meta_type'] = 'NUMERIC';
                    break;

                case 'popular':
                    $query_args['meta_key'] = '_views';
                    $query_args['orderby']  = 'meta_value_num';
                    $query_args['order']    = 'DESC';
                    $query_args['meta_type'] = 'NUMERIC';
                    break;

                default:
                    $filter = '';
                    break;
            }

            if ( $filter ) {
                $filter_query = new WP_Query( $query_args );
            }
        }
        ?>

        <header class="entry-header">
            <h1 class="entry-title"><i class="fa fa-video-camera"></i> Videos</h1>
        </header>

        <?php if ( $filter && $filter_query instanceof WP_Query ) : ?>

            <?php if ( $filter_query->have_posts() ) : ?>
                <?php
                while ( $filter_query->have_posts() ) :
                    $filter_query->the_post();
                    get_template_part( 'template-parts/loop', 'video' );
                endwhile;
                wp_reset_postdata();
                ?>
            <?php else : ?>
                <p><?php esc_html_e( 'No videos found for this filter.', 'retrotube-child' ); ?></p>
            <?php endif; ?>

        <?php elseif ( is_page( 'videos' ) ) : ?>

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
