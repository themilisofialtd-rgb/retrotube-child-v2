<?php
/**
 * Archive template for the Models CPT.
 */
get_header();
?>
<div id="primary" class="content-area container">
  <div class="row">
    <main id="main" class="site-main col-md-8">
      <?php get_template_part('breadcrumb'); ?>

      <?php
      // Pull content from the Page with slug "models"
      $models_page = get_page_by_path('models');
      if ($models_page instanceof WP_Post) {
        echo '<div class="models-intro">';
        echo apply_filters('the_content', $models_page->post_content);
        echo '</div>';
      }
      ?>

      <?php echo do_shortcode('[actors_flipboxes]'); ?>
    </main>
    <aside class="col-md-4">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</div>
<?php get_footer(); ?>
