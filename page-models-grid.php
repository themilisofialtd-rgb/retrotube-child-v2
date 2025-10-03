<?php
/**
 * Template Name: Models Grid (Flipbox)
 * Description: Shows the models flipbox grid with pagination and sidebar.
 */
get_header();
?>
<div class="tmw-title"><span class="tmw-star">★</span>Models</div>
<div class="tmw-layout">
  <main id="primary" class="site-main">
    <?php
      // 12 per page, 3 columns; banner after 6 is handled by the shortcode logic.
      echo do_shortcode('[actors_flipboxes per_page="12" cols="3" show_pagination="true" page_var="pg"]');
    ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
