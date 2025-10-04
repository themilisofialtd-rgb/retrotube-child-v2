<?php
/**
 * Template Name: Videos Page
 */
get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <header class="entry-header">
            <h1 class="entry-title"><i class="fa fa-video-camera"></i> Videos</h1>
        </header>

        <?php
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
        $args = array(
            'post_type'      => 'video',
            'posts_per_page' => get_option('posts_per_page'),
            'paged'          => $paged,
        );
        $videos = new WP_Query($args);

        if ($videos->have_posts()) :
            while ($videos->have_posts()) : $videos->the_post();
                get_template_part('template-parts/content', 'video');
            endwhile;

            the_posts_pagination();

        else :
            echo '<p>No videos found.</p>';
        endif;

        wp_reset_postdata();
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
