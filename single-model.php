<?php
/**
 * Single template for the Models CPT.
 *
 * Restores the stable RetroTube layout that leverages the shared breadcrumbs
 * and ACF bio partials while keeping the parent theme sidebar intact.
 */

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right single-model">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/breadcrumbs'); ?>
          <?php get_template_part('template-parts/single-model_bio'); ?>
        <?php endwhile; ?>
      <?php endif; ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php get_footer(); ?>
