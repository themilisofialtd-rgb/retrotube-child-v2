<?php
/**
 * Reusable Featured Models block partial.
 */

if (!defined('ABSPATH')) {
  exit;
}

$model_ids = get_posts([
  'post_type'         => 'model',
  'post_status'       => 'publish',
  'fields'            => 'ids',
  'posts_per_page'    => 4,
  'orderby'           => 'rand',
  'no_found_rows'     => true,
  'suppress_filters'  => false,
]);

if (empty($model_ids)) {
  return;
}

$ids = implode(',', array_map('intval', $model_ids));

if (!$ids) {
  return;
}

$shortcode = sprintf('[actors_flipboxes ids="%s"]', esc_attr($ids));
$flipboxes = do_shortcode($shortcode);

if (trim($flipboxes) === '') {
  return;
}
?>
<div class="model-flipbox" style="margin:40px 0;">
  <div class="tmwfm-wrap">
    <h3 class="tmwfm-heading"><?php esc_html_e('Featured Models', 'retrotube-child'); ?></h3>
    <div class="tmwfm-grid">
      <?php echo $flipboxes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </div>
</div>
