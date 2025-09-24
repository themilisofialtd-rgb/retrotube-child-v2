<?php
/**
 * Archive template for the Models CPT.
 */
get_header();
?>
<div class="container">
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
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
