<?php
/**
 * Single template for the Models CPT.
 *
 * Reuse the existing Model Bio template so both slugs share the same layout
 * and breadcrumb logic.
 */

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right single-model">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('breadcrumb'); ?>
          <?php get_template_part('single-model_bio'); ?>
        <?php endwhile; ?>
      <?php endif; ?>
      <?php tmw_featured_models_block(); ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php get_footer(); ?>
