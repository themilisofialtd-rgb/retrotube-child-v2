<?php
/**
 * Retrotube Child (Flipbox Edition) v2
 * - Enqueues parent styles and child CSS
 * - Actors Flipboxes shortcode with pagination, banner slot, trigger button
 * - Promo flipboxes shortcode (4 items, external links)
 */

// Load parent CSS.
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('retrotube-parent', get_template_directory_uri() . '/style.css');
});

// Child CSS.
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('rt-child-flip', get_stylesheet_directory_uri() . '/assets/flipboxes.css', [], '1.1.0');
});

/**
 * Helper: total term count for a taxonomy (hide_empty aware)
 */
function tmw_count_terms($taxonomy, $hide_empty=false){
  if ( function_exists('wp_count_terms') ) {
    $count = wp_count_terms([
      'taxonomy'   => $taxonomy,
      'hide_empty' => $hide_empty
    ]);
    if ( ! is_wp_error($count) ) return (int)$count;
  }
  // Fallback
  $ids = get_terms([ 'taxonomy'=>$taxonomy, 'fields'=>'ids', 'hide_empty'=>$hide_empty ]);
  return is_wp_error($ids) ? 0 : count($ids);
}

/**
 * Shortcode: [actors_flipboxes per_page="16" cols="4" orderby="name" order="ASC" hide_empty="false"
 *             trigger_text="See profile" banner_img="" banner_url="" banner_alt=""
 *             show_pagination="true" page_var="pg"]
 */
add_shortcode('actors_flipboxes', function($atts){
  $a = shortcode_atts([
    'per_page'       => 16,
    'cols'           => 4,
    'orderby'        => 'name',
    'order'
