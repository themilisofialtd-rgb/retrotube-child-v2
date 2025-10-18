<?php
/**
 * Shared banner frame helpers for the model banner meta box.
 */

if (!defined('ABSPATH')) {
  exit;
}

add_action('admin_enqueue_scripts', function ($hook) {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;

  $should_enqueue = false;

  if ($hook === 'term.php' && $screen && $screen->taxonomy === 'models') {
    $should_enqueue = true;
  } elseif ($hook === 'post.php') {
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($post_id && get_post_type($post_id) === 'model') {
      $should_enqueue = true;
    }
  }

  if (!$should_enqueue) {
    return;
  }

  wp_enqueue_style(
    'retrotube-child-style',
    get_stylesheet_uri(),
    [],
    tmw_child_style_version()
  );

  wp_enqueue_style('tmw-admin-banner-style');
});
