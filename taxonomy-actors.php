<?php
/**
 * Single Model (actors taxonomy) – Biography + optional links + sidebar.
 * This does NOT render the flipbox grid (that stays on the "Models Grid (Flipbox)" page template).
 */
get_header();

$term     = get_queried_object();
$term_id  = isset($term->term_id) ? (int)$term->term_id : 0;
$acf_id   = 'actors_' . $term_id;

// Photo (ACF front, then term thumbnail, else empty)
$front    = function_exists('get_field') ? get_field('actor_card_front', $acf_id) : null;
$front_url = (is_array($front) && !empty($front['url'])) ? $front['url'] : '';
if (!$front_url) {
  $thumb_id  = (int) get_term_meta($term_id, 'thumbnail_id', true);
  if ($thumb_id) {
    $front_url = wp_get_attachment_image_url($thumb_id, 'large');
  }
}

// Description (term description supports HTML entered in admin)
$bio_html = term_description($term_id, 'actors');

// Optional socials (any that exist will be shown)
$social_keys = ['onlyfans','fancentro','twitter','instagram','facebook','reddit','tiktok','website'];
$socials = [];
if (function_exists('get_field')) {
  foreach ($social_keys as $k) {
    // Try "actor_{key}" then just "{key}"
    $v = get_field('actor_'.$k, $acf_id);
    if (!$v) $v = get_field($k, $acf_id);
    if ($v)   $socials[$k] = esc_url($v);
  }
}
?>

<div class="tmw-title"><span class="tmw-star">★</span><?php echo esc_html($term->name); ?></div>

<div class="tmw-layout">
  <main id="primary" class="site-main">
    <article class="tmw-actor">
      <?php if ($front_url): ?>
        <figure class="tmw-actor-photo">
          <img src="<?php echo esc_url($front_url); ?>" alt="<?php echo esc_attr($term->name); ?>" loading="eager" fetchpriority="high" decoding="async" />
        </figure>
      <?php endif; ?>

      <?php if ($bio_html): ?>
        <div class="tmw-actor-bio">
          <?php echo wp_kses_post($bio_html); ?>
        </div>
      <?php else: ?>
        <div class="tmw-actor-bio tmw-actor-bio--empty">
          <p>No biography provided yet.</p>
        </div>
      <?php endif; ?>

      <?php if (!empty($socials)): ?>
        <div class="tmw-actor-social">
          <?php foreach ($socials as $label => $url): ?>
            <a class="tmw-social" href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow noopener">
              <?php echo esc_html(ucfirst($label)); ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>

    <?php
    // Optional: if you later want to list this model's videos below the bio,
    // add a custom WP_Query here filtered by this term and render as you like.
    ?>
  </main>

  <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
