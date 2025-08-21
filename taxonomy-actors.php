<?php
/**
 * Single Model (actors taxonomy) – Biography + optional links + sidebar.
 * This does NOT render the flipbox grid (that stays on the "Models Grid (Flipbox)" page template).
 */
get_header();

// Read ACF fields and build hero/banner + biography + live link
$term     = get_queried_object();
$term_id  = isset($term->term_id) ? (int)$term->term_id : 0;
$acf_id   = 'actors_' . $term_id;

// --- Read ACF fields with robust fallbacks for possible field names ---
$hero    = null;
foreach ([
  'hero_image','tmw_hero_image','actor_hero','actor_header','header_image','actor_card_front'
] as $key) {
  $v = function_exists('get_field') ? get_field($key, $acf_id) : null;
  if ($v) { $hero = $v; break; }
}

$short_bio = '';
foreach (['short_bio','tmw_short_bio','bio','about'] as $key) {
  $v = function_exists('get_field') ? get_field($key, $acf_id) : '';
  if (!empty($v)) { $short_bio = (string)$v; break; }
}

$live_link = '';
foreach (['live_link','tmw_live_link','affiliate_url','chat_url','external_url'] as $key) {
  $v = function_exists('get_field') ? get_field($key, $acf_id) : '';
  if (!empty($v)) { $live_link = (string)$v; break; }
}

// --- Build hero banner HTML (prefer ACF hero, then term thumbnail, then nothing) ---
$hero_html = '';
if (is_array($hero) && !empty($hero['ID'])) {
  // ACF returns image array
  $hero_html = wp_get_attachment_image($hero['ID'], 'tmw-actor-hero-banner', false, [
    'class'         => 'tmw-actor-hero-img',
    'alt'           => $term->name,
    'loading'       => 'eager',
    'fetchpriority' => 'high',
    'decoding'      => 'async',
    'sizes'         => '(max-width: 1024px) 100vw, 720px',
  ]);
} elseif (is_array($hero) && !empty($hero['url'])) {
  // ACF returns simple url
  $hero_html = '<img class="tmw-actor-hero-img" src="'.esc_url($hero['url']).'" alt="'.esc_attr($term->name).'" loading="eager" fetchpriority="high" decoding="async" />';
} else {
  // Fallback: taxonomy thumbnail ID
  $thumb_id = (int) get_term_meta($term_id, 'thumbnail_id', true);
  if ($thumb_id) {
    $hero_html = wp_get_attachment_image($thumb_id, 'tmw-actor-hero-banner', false, [
      'class'         => 'tmw-actor-hero-img',
      'alt'           => $term->name,
      'loading'       => 'eager',
      'fetchpriority' => 'high',
      'decoding'      => 'async',
    ]);
  }
}

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
      <?php if ($hero_html) { echo '<figure class="tmw-actor-hero">'.$hero_html.'</figure>'; } ?>

      <?php
      // Biography: prefer ACF short_bio; fallback to term description
      if (!empty($short_bio)) {
        echo '<div class="tmw-bio">'.wp_kses_post(wpautop($short_bio)).'</div>';
      } else {
        $desc = term_description($term_id, 'actors');
        if (!empty($desc)) {
          echo '<div class="tmw-bio">'.wp_kses_post($desc).'</div>';
        } else {
          echo '<div class="tmw-bio tmw-bio-empty">No biography provided yet.</div>';
        }
      }

      // Live link button (optional)
      if (!empty($live_link)) {
        echo '<p class="tmw-live"><a class="tmw-live-btn" href="'.esc_url($live_link).'" target="_blank" rel="nofollow sponsored noopener">Live chat &raquo;</a></p>';
      }
      ?>

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
