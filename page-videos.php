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
        if ( have_posts() ) :
            while ( have_posts() ) : the_post();
                get_template_part( 'template-parts/content', get_post_format() );
            endwhile;

            the_posts_pagination();
        else :
            get_template_part( 'template-parts/content', 'none' );
        endif;
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
