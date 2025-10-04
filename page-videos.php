<?php
/**
 * Template Name: Videos Page
 */
get_header(); ?>

<div id="primary" class="content-area with-sidebar-right">
    <main id="main" class="site-main with-sidebar-right" role="main">

        <?php
        $filter       = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : '';
        $filter_query = null;

        if ( $filter ) {
            $current_page = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
            $query_args = array(
                'post_type'           => 'video',
                'post_status'         => 'publish',
                'posts_per_page'      => 12,
                'ignore_sticky_posts' => true,
                'paged'               => $current_page,
            );

            if ( 'latest' === $filter ) {
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
            } elseif ( 'random' === $filter ) {
                $query_args['orderby'] = 'rand';
            } elseif ( 'longest' === $filter ) {
                $query_args['meta_key']   = 'duration';
                $query_args['orderby']    = 'meta_value_num';
                $query_args['order']      = 'DESC';
                $query_args['meta_type']  = 'NUMERIC';
                $query_args['meta_query'] = array(
                    array(
                        'key'     => 'duration',
                        'compare' => 'EXISTS',
                    ),
                );
            } elseif ( 'popular' === $filter ) {
                $query_args['meta_key']   = 'post_views_count';
                $query_args['orderby']    = 'meta_value_num';
                $query_args['order']      = 'DESC';
                $query_args['meta_type']  = 'NUMERIC';
                $query_args['meta_query'] = array(
                    array(
                        'key'     => 'post_views_count',
                        'compare' => 'EXISTS',
                    ),
                );
            } elseif ( is_numeric( $filter ) ) {
                $query_args['cat'] = absint( $filter );
            }

            $filter_query = new WP_Query( $query_args );
        }
        ?>

        <header class="entry-header">
            <h1 class="entry-title"><i class="fa fa-video-camera"></i> Videos</h1>
        </header>

        <?php if ( $filter && $filter_query instanceof WP_Query ) : ?>

            <?php if ( $filter_query->have_posts() ) : ?>
                <section class="videos-filter-results">
                    <?php
                    while ( $filter_query->have_posts() ) :
                        $filter_query->the_post();
                        get_template_part( 'template-parts/loop', 'video' );
                    endwhile;
                    ?>
                </section>

                <?php
                the_posts_pagination(
                    array(
                        'total'   => (int) $filter_query->max_num_pages,
                        'current' => isset( $current_page ) ? $current_page : 1,
                        'add_args' => array(
                            'filter' => $filter,
                        ),
                    )
                );
                ?>
            <?php else : ?>
                <p><?php esc_html_e( 'No videos found for this filter.', 'retrotube-child' ); ?></p>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>

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
