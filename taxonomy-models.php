<?php
/**
 * Models taxonomy archive.
 * Displays the flipbox grid, matching the Models Grid page layout.
 */
get_header();
?>
<div class="tmw-title">
  <span class="tmw-star">★</span>
  <h1 class="tmw-title-text">Models</h1>
</div>
<div class="tmw-layout">
  <main id="primary" class="site-main">
    <?php get_template_part('breadcrumb'); ?>
    <?php
    echo do_shortcode('[actors_flipboxes per_page="12" cols="3" show_pagination="true" page_var="pg"]');
    ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
