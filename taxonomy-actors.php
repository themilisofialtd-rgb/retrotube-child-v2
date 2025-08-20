<?php
/**
 * Template for Actors taxonomy term (actor profile)
 * - Shows hero image only; bio + links appear UNDER the image
 */

$term = get_queried_object();
if ( ! $term || ! isset($term->term_id) ) { get_template_part('index'); exit; }

// ACF fields
$hero      = function_exists('get_field') ? get_field('hero_image', 'actors_'.$term->term_id) : null;
$hero_url  = is_array($hero) && !empty($hero['url']) ? $hero['url'] : '';
$short_bio = function_exists('get_field') ? get_field('short_bio', 'actors_'.$term->term_id) : '';
$live_link = function_exists('get_field') ? get_field('live_link', 'actors_'.$term->term_id) : '';

// Optional socials (any may be empty)
$links = [];
if ( function_exists('get_field') ) {
  $map = [
    'Website'   => 'link_website',
    'OnlyFans'  => 'link_onlyfans',
    'FanCentro' => 'link_fancentro',
    'Reddit'    => 'link_reddit',
    'Facebook'  => 'link_facebook',
    'Instagram' => 'link_instagram',
    'Twitter/X' => 'link_twitter',
    'TikTok'    => 'link_tiktok',
  ];
  foreach ($map as $label => $key){
    $u = trim((string)get_field($key, 'actors_'.$term->term_id));
    if ( !empty($u) ) $links[] = ['label'=>$label, 'url'=>$u];
  }
}

get_header(); ?>
<main id="primary" class="site-main actors-page">

  <!-- Hero image only -->
  <section class="actor-hero container">
    <?php if ($hero_url): ?>
      <img src="<?php echo esc_url($hero_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
    <?php endif; ?>
  </section>

  <!-- Info under the hero -->
  <section class="actor-info container">
    <h1 class="actor-name"><?php echo esc_html($term->name); ?></h1>

    <?php if ( !empty($links) ): ?>
      <div class="actor-links">
        <?php foreach ($links as $l): ?>
          <a class="actor-link" href="<?php echo esc_url($l['url']); ?>" target="_blank" rel="nofollow noopener sponsored">
            <?php echo esc_html($l['label']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($short_bio): ?>
      <div class="actor-bio"><?php echo wp_kses_post( wpautop($short_bio) ); ?></div>
    <?php endif; ?>

    <?php if ($live_link): ?>
      <a href="<?php echo esc_url($live_link); ?>" target="_blank" rel="sponsored noopener nofollow" class="btn btn-primary">
        Watch <?php echo esc_html($term->name); ?> Live
      </a>
    <?php endif; ?>
  </section>

  <!-- Videos grid filtered by this actor -->
  <section class="actor-videos container">
    <h2 class="section-title">Videos featuring <?php echo esc_html($term->name); ?></h2>

    <?php
    $paged = max(1, intval(get_query_var('paged')));
    $q = new WP_Query([
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'paged'          => $paged,
      'tax_query'      => [[
        'taxonomy' => 'actors',
        'field'    => 'term_id',
        'terms'    => $term->term_id,
      ]],
    ]);

    if ( $q->have_posts() ) :
      echo '<div class="video-grid">';
      while ( $q->have_posts() ) : $q->the_post(); ?>
        <article <?php post_class('video-card'); ?>>
          <a href="<?php the_permalink(); ?>" class="thumb" aria-label="<?php the_title_attribute(); ?>">
            <?php if ( has_post_thumbnail() ) {
              the_post_thumbnail('medium_large', ['loading'=>'lazy']);
            } ?>
          </a>
          <h3 class="video-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
          <div class="video-meta">
            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
          </div>
        </article>
      <?php endwhile;
      echo '</div>';

      echo '<div class="tmw-pagination">';
      echo paginate_links([
        'total'   => $q->max_num_pages,
        'current' => $paged,
      ]);
      echo '</div>';

      wp_reset_postdata();
    else:
      echo '<p>No videos found for this actor yet.</p>';
    endif; ?>
  </section>

</main>
<?php get_footer();