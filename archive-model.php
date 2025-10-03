<?php
/**
 * Archive template for the Models CPT.
 */
get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right models-archive">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php get_template_part('breadcrumb'); ?>
      <?php
      echo do_shortcode('[actors_flipboxes per_page="12" cols="3" show_pagination="true" page_var="pg"]');
      ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php
get_footer();
