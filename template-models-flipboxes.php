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
      $tmw_flipbox_link_filter = function ($link, $term) {
        $is_mobile = wp_is_mobile();

        // 🔒 On mobile: disable link entirely to stop scroll or navigation
        if ($is_mobile) {
          return false; // returning false tells the rendering function: no <a> wrapper
        }

        $home_url = trailingslashit(home_url('/'));
        $current  = is_string($link) ? trailingslashit($link) : '';

        if ($current && $current !== $home_url) {
          return $link;
        }

        if (function_exists('tmw_get_model_post_for_term')) {
          $post = tmw_get_model_post_for_term($term);
          if ($post instanceof WP_Post) {
            $post_link = get_permalink($post);
            if ($post_link) {
              return $post_link;
            }
          }
        }

        if (!$current || $current === $home_url) {
          $term_obj = $term;
          if (is_numeric($term)) {
            $term_obj = get_term((int) $term, 'models');
          }
          if ($term_obj && !is_wp_error($term_obj)) {
            $term_link = get_term_link($term_obj);
            if (!is_wp_error($term_link) && $term_link) {
              return $term_link;
            }
          }
        }

        return $link;
      };

      add_filter('tmw_model_flipbox_link', $tmw_flipbox_link_filter, 10, 2);
      echo tmw_models_flipboxes_cb([
        'per_page'        => 16,
        'cols'            => 4,
        'show_pagination' => true,
      ]);
      remove_filter('tmw_model_flipbox_link', $tmw_flipbox_link_filter, 10);
      ?>
    </section>
    <aside class="tmw-sidebar">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</main>
<?php get_footer(); ?>
