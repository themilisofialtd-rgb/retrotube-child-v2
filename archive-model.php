<?php
/**
 * Archive template for the Models CPT.
 */
get_header();
?>
<main id="primary" class="site-main">
    <div class="tmw-layout container model-archive-layout">
        <section class="tmw-content">
            <h1 class="tmw-title"><span class="tmw-star">★</span><?php esc_html_e('Models', 'retrotube-child'); ?></h1>
            <?php
            $archive_description = get_the_archive_description();
            if ($archive_description) :
                ?>
                <div class="archive-description"><?php echo wp_kses_post($archive_description); ?></div>
                <?php
            endif;

            echo do_shortcode('[actors_flipboxes per_page="16" cols="4" show_pagination="true"]');
            ?>
        </section>

        <aside class="tmw-sidebar">
            <?php get_sidebar(); ?>
        </aside>
    </div>
</main>

<?php get_footer(); ?>
