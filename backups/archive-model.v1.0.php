<?php
/**
 * Archive template for the Models CPT.
 */
get_header();
?>
<div class="tmw-layout">
  <main id="primary" class="site-main">
    <?php
      echo do_shortcode('[actors_flipboxes per_page="12" cols="3" show_pagination="true" page_var="pg"]');
    ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
