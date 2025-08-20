<?php
/**
 * Template Name: Models Flipboxes (with Sidebar)
 * Description: Displays an Actors flipbox grid with pagination, sidebar, and a banner slot.
 */
get_header(); ?>
<main id="primary" class="site-main">
  <div class="tmw-layout container">
    <section class="tmw-content">
      <h1 class="section-title">Models</h1>
      <?php
      // Edit banner file at /assets/models-banner.html or pass banner_* via shortcode below.
      echo do_shortcode('[actors_flipboxes per_page="16" cols="4" show_pagination="true"]');
      ?>
    </section>
    <aside class="tmw-sidebar">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</main>
<?php get_footer();