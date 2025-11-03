<?php
/**
 * Template part for displaying a single model's hero, bio, and supporting meta.
 */

// Get ACF fields.
$bio               = function_exists('get_field') ? get_field('bio') : '';
$model_link_field  = function_exists('get_field') ? get_field('model_link') : '';
$banner            = function_exists('get_field') ? get_field('banner_image') : '';
$model_title       = get_the_title();

$banner_url = '';
$banner_alt = '';
if (is_array($banner)) {
    $banner_url = isset($banner['url']) && is_string($banner['url']) ? $banner['url'] : '';
    $banner_alt = isset($banner['alt']) ? $banner['alt'] : '';
} elseif (is_string($banner)) {
    $banner_url = $banner;
}

$model_link_url = '';
if (is_array($model_link_field)) {
    $model_link_url = isset($model_link_field['url']) && is_string($model_link_field['url']) ? $model_link_field['url'] : '';
} elseif (is_string($model_link_field)) {
    $model_link_url = $model_link_field;
}
?>

<article <?php post_class('model-bio-page'); ?>>
  <header class="model-header" style="text-align: center; margin-bottom: 30px;">
    <h1 class="model-title"><?php the_title(); ?></h1>

    <?php if (!empty($banner_url)) : ?>
      <?php $alt_text = !empty($banner_alt) ? $banner_alt : $model_title; ?>
      <div class="model-hero">
        <img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr($alt_text); ?>" style="max-width:100%; height:auto; margin-top: 15px;">
      </div>
    <?php endif; ?>
  </header>

  <div class="model-content">
    <?php if ($bio) : ?>
      <div class="model-bio" style="margin-bottom: 30px;">
        <?php echo wp_kses_post($bio); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($model_link_url)) : ?>
      <div class="model-link" style="text-align: center; margin-top: 40px;">
        <a href="<?php echo esc_url($model_link_url); ?>" class="btn" target="_blank" rel="nofollow">
          <?php printf(
            /* translators: %s is the model name */
            esc_html__('Visit %s on LiveJasmin', 'retrotube-child'),
            esc_html($model_title)
          ); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</article>
