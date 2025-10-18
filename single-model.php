<?php
/**
 * Template Name: Single Model
 * Description: Identical to RetroTube single-video.php but displays model banner instead of video player
 */

wp_enqueue_style('retrotube-child-style');
wp_enqueue_style('rt-child-flip');

get_header();
?>
<div id="primary" class="content-area with-sidebar-right">
        <main id="main" class="site-main with-sidebar-right" role="main">
                <?php
                if ( have_posts() ) :
                        while ( have_posts() ) :
                                the_post();
                                get_template_part( 'template-parts/content', 'model' );
                        endwhile;
                endif;
                ?>
        </main><!-- #main -->
</div><!-- #primary -->

<?php get_sidebar(); ?>

<?php
get_footer();
