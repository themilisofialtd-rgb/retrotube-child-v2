<?php
/**
 * Template Name: Videos Page
 */
get_header();
?>

<div id="primary" class="content-area with-sidebar-right">
    <main id="main" class="site-main with-sidebar-right" role="main">

        <header class="entry-header">
            <h1 class="entry-title"><i class="fa fa-video-camera"></i> Videos</h1>
        </header>

        <?php
        // Mirror the homepage loop by querying the main post feed.
        $paged = get_query_var( 'paged' );

        if ( ! $paged ) {
            $paged = get_query_var( 'page' );
        }

        if ( ! $paged ) {
            $paged = 1;
        }

        $videos_query = new WP_Query(
            array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'paged'          => $paged,
                'ignore_sticky_posts' => 0,
            )
        );

        global $wp_query;

        $original_wp_query = $wp_query;
        $wp_query          = $videos_query;

        if ( $videos_query->have_posts() ) :
            while ( $videos_query->have_posts() ) :
                $videos_query->the_post();
                get_template_part( 'template-parts/content', get_post_format() );
            endwhile;

            the_posts_pagination();
        else :
            get_template_part( 'template-parts/content', 'none' );
        endif;

        $wp_query = $original_wp_query;
        wp_reset_postdata();
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
