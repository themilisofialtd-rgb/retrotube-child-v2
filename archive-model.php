<?php
/**
 * Archive template for the Models CPT.
 */
get_header();
?>
<div class="container model-archive-page">
    <?php tmw_render_models_breadcrumbs([
        'show_current'    => false,
        'container_style' => 'margin:15px 0;',
    ]); ?>
</div>
<div class="tmw-layout model-archive-layout">
    <main id="primary" class="site-main">
        <header class="page-header">
            <h1 class="page-title"><?php esc_html_e('Models', 'retrotube-child'); ?></h1>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="model-archive-list">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('model-archive-item'); ?>>
                        <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                        <?php if (has_post_thumbnail()) : ?>
                            <a class="model-archive-thumb" href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium_large'); ?>
                            </a>
                        <?php endif; ?>

                        <div class="entry-summary">
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination([
                'mid_size'  => 2,
                'prev_text' => esc_html__('Previous', 'retrotube-child'),
                'next_text' => esc_html__('Next', 'retrotube-child'),
            ]); ?>
        <?php else : ?>
            <p class="no-models-found"><?php esc_html_e('No models found.', 'retrotube-child'); ?></p>
        <?php endif; ?>
    </main>

    <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
