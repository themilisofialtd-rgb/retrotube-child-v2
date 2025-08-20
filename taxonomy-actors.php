<?php
/**
 * Actors archive with sidebar + custom title + child grid
 * Theme: Retrotube Child (Flipbox Edition) v2
 */
if ( ! defined('ABSPATH') ) exit;
get_header();
?>

<div class="tmw-layout">
  <main id="primary" class="site-main">
    <div class="tmw-title"><span class="tmw-star">★</span> Actors</div>
    <?php
      // 12 per page, 4 columns, our pagination + banner gap handled in the shortcode
      echo do_shortcode('[actors_flipboxes per_page="12" cols="4" show_pagination="true" page_var="pg"]');
    ?>
  </main>

  <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
