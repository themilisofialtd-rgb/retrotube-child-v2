<?php
/**
 * Single Model (actors taxonomy) – Biography + optional links + sidebar.
 * This does NOT render the flipbox grid (that stays on the "Models Grid (Flipbox)" page template).
 */
get_header();

$term     = get_queried_object();
$term_id  = isset($term->term_id) ? (int)$term->term_id : 0;
$acf_id   = 'actors_' . $term_id;

// Build hero banner HTML (ACF "front" image preferred; otherwise taxonomy thumbnail)
$front     = function_exists('get_field') ? get_field('actor_card_front', 'actors_' . $term_id) : null;
$hero_html = '';

if (is_array($front) && !empty($front['ID'])) {
  $hero_html = wp_get_attachment_image($front['ID'], 'tmw-actor-hero-banner', false, [
    'class'         => 'tmw-actor-hero-img',
    'alt'           => $term->name,
    'loading'       => 'eager',
    'fetchpriority' => 'high',
    'decoding'      => 'async',
    'sizes'         => '(max-width: 1024px) 100vw, 720px',
  ]);
} else {
  $thumb_id = (int) get_term_meta($term_id, 'thumbnail_id', true);
  if ($thumb_id) {
    $hero_html = wp_get_attachment_image($thumb_id, 'tmw-actor-hero-banner', false, [
      'class'         => 'tmw-actor-hero-img',
      'alt'           => $term->name,
      'loading'       => 'eager',
      'fetchpriority' => 'high',
      'decoding'      => 'async',
      'sizes'         => '(max-width: 1024px) 100vw, 720px',
    ]);
  }
}

// Output hero above the biography
if ($hero_html) {
  echo '<figure class="tmw-actor-hero">' . $hero_html . '</figure>';
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
