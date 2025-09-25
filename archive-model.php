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
      <?php
      if (tmw_should_output_featured_block() && tmw_featured_block_dedup()) {
          $shortcode = tmw_get_featured_shortcode_for_context();
          set_query_var('tmw_featured_shortcode', $shortcode);
          get_template_part('partials/featured-models-block');
          set_query_var('tmw_featured_shortcode', null);
      }
      ?>
    </main>
    <aside class="col-md-4">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</div>
<?php get_footer(); ?>
