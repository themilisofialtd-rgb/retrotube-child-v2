<?php
/**
 * Archive template for the Models CPT.
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
    $has_models = false;

    if (function_exists('tmw_count_terms')) {
      $has_models = tmw_count_terms('models', false) > 0;
    } else {
      $term_check = get_terms([
        'taxonomy'   => 'models',
        'hide_empty' => false,
        'number'     => 1,
        'fields'     => 'ids',
      ]);
      $has_models = !is_wp_error($term_check) && !empty($term_check);
    }

    if ($has_models) {
      echo do_shortcode('[actors_flipboxes per_page="12" cols="3" show_pagination="true" page_var="pg"]');
    } else {
      echo '<p class="no-models-found">No models found.</p>';
    }
    ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
