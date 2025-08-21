<?php
/**
 * Retrotube Child (Flipbox Edition) v2
 * - Enqueues parent styles and child CSS
 * - Actors Flipboxes shortcode with pagination, banner slot
 * - Promo flipboxes shortcode (4 items, external links)
 */

// Styles and lightweight optimizations.
add_action('wp_enqueue_scripts', function () {
  // Parent + child styles.
  wp_enqueue_style('retrotube-parent', get_template_directory_uri() . '/style.css');
  wp_enqueue_style('rt-child-flip', get_stylesheet_directory_uri() . '/assets/flipboxes.css', ['retrotube-parent'], '1.1.0');

  // Remove unused default assets for better performance.
  wp_dequeue_style('wp-block-library');
  wp_dequeue_style('wp-block-library-theme');
  wp_dequeue_style('wc-blocks-style');
  wp_deregister_script('wp-embed');
}, 20);

// Disable emojis.
add_action('init', function () {
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('admin_print_scripts', 'print_emoji_detection_script');
  remove_action('wp_print_styles', 'print_emoji_styles');
  remove_action('admin_print_styles', 'print_emoji_styles');
  remove_filter('the_content_feed', 'wp_staticize_emoji');
  remove_filter('comment_text_rss', 'wp_staticize_emoji');
  remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
  add_filter('emoji_svg_url', '__return_false');
});

// LCP: prioritize first above-the-fold image on listings
add_filter('post_thumbnail_html', function($html){
  static $done = false;
  if ( ! $done && (is_home() || is_archive()) ) {
    $html = preg_replace('#\\sloading=("|\')lazy("|\')#i', '', $html);
    $html = preg_replace('#<img\\s#', '<img fetchpriority="high" decoding="async" ', $html, 1);
    $done = true;
  }
  return $html;
}, 10, 5);

add_action('wp_head', function(){
  if ( !is_home() && !is_archive() ) return;
  echo '<script>\n';
  echo 'document.addEventListener("DOMContentLoaded", function () {\n';
  echo '  var img = document.querySelector(".video-grid img, .tmw-grid img");\n';
  echo '  if (img) {\n';
  echo '    img.setAttribute("fetchpriority", "high");\n';
  echo '    img.setAttribute("decoding", "async");\n';
  echo '    img.removeAttribute("loading");\n';
  echo '  }\n';
  echo '});\n';
  echo '</script>\n';
});

add_filter('wp_resource_hints', function($urls, $relation_type){
  if ( 'preconnect' === $relation_type ) {
    $urls[] = 'https://galleryn3.vcmdawe.com';
  }
  if ( 'dns-prefetch' === $relation_type ) {
    $urls[] = '//galleryn3.vcmdawe.com';
  }
  return $urls;
}, 10, 2);

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
 * Shortcode: [actors_flipboxes per_page="12" cols="4" orderby="name" order="ASC" hide_empty="false"
 *             banner_img="" banner_url="" banner_alt=""
 *             show_pagination="true" page_var="pg"]
 */
add_shortcode('actors_flipboxes', function($atts){
  $a = shortcode_atts([
    'per_page'       => 16,   // A) CHANGED: 12 per page (was 16)
    'cols'           => 4,
    'orderby'        => 'name',
    'order'          => 'ASC',
    'hide_empty'     => false,
    'banner_img'     => '',
    'banner_url'     => '',
    'banner_alt'     => 'Sponsored',
    'banner_html'    => '',
    'show_pagination'=> true,
    'page_var'       => 'pg',
  ], $atts);

  // Current page number (supports /?pg=2 on static pages).
  $paged = 1;
  if ( isset($_GET[$a['page_var']]) ) {
    $paged = max(1, intval($_GET[$a['page_var']]));
  } else {
    $paged = max(1, intval(get_query_var('paged')), intval(get_query_var('page')));
  }

  $per_page   = max(1, intval($a['per_page']));
  $offset     = ($paged - 1) * $per_page;
  $hide_empty = filter_var($a['hide_empty'], FILTER_VALIDATE_BOOLEAN);

  $args = [
    'taxonomy'   => 'actors',
    'hide_empty' => $hide_empty,
    'orderby'    => $a['orderby'],
    'order'      => $a['order'],
    'number'     => $per_page,
    'offset'     => $offset,
  ];
  $terms = get_terms($args);
  if ( is_wp_error($terms) ) return '';

  $total   = tmw_count_terms('actors', $hide_empty);
  $total_p = max(1, (int)ceil($total / $per_page));

  ob_start();
  printf('<div class="tmw-grid tmw-cols-%d">', (int)$a['cols']);

  $i = 0;
  foreach ($terms as $term){

    // ACF images (optional)
    $front = function_exists('get_field') ? get_field('actor_card_front', 'actors_'.$term->term_id) : null;
    $back  = function_exists('get_field') ? get_field('actor_card_back',  'actors_'.$term->term_id) : null;
    $front_url = is_array($front) && !empty($front['url']) ? $front['url'] : '';
    $back_url  = is_array($back)  && !empty($back['url'])  ? $back['url']  : $front_url;

    $link = get_term_link($term);

    // === FRONT: actor name with small red arrow; BACK: red "View profile" ===
    echo '<a class="tmw-flip" href="'.esc_url($link).'" aria-label="'.esc_attr($term->name).'">
            <div class="tmw-flip-inner">
              <div class="tmw-flip-front" style="background-image:url('.esc_url($front_url).');">
                <span class="tmw-name">'.esc_html($term->name).'</span>
              </div>
              <div class="tmw-flip-back" style="background-image:url('.esc_url($back_url).');">
                <span class="tmw-view">View profile</span>
              </div>
            </div>
          </a>';

    $i++;

    // B) CHANGED: Inject banner after the 8th item (was 8th).
    if ( $i === 8 ) {
      $banner_html = '';
      if ( !empty($a['banner_html']) ) {
        $banner_html = $a['banner_html'];
      } else if ( !empty($a['banner_img']) && !empty($a['banner_url']) ) {
        $banner_html = '<a class="tmw-banner" href="'.esc_url($a['banner_url']).'" target="_blank" rel="sponsored nofollow noopener">
                          <img src="'.esc_url($a['banner_img']).'" alt="'.esc_attr($a['banner_alt']).'" width="364" height="45">
                        </a>';
      } else {
        $banner_file = get_stylesheet_directory() . '/assets/models-banner.html';
        if ( is_readable($banner_file) ) {
          $banner_html = file_get_contents($banner_file);
        }
      }
      // Always reserve space even if we have no banner content
      if ( !empty($banner_html) ) {
        echo '<div class="tmw-banner-wrap">'.$banner_html.'</div>';
      } else {
        echo '<div class="tmw-banner-wrap tmw-banner-empty" aria-hidden="true"></div>';
      }
    }
  }
  echo '</div>';

  // Pagination
  if ( filter_var($a['show_pagination'], FILTER_VALIDATE_BOOLEAN) && $total_p > 1 ){
    $base = remove_query_arg($a['page_var']);
    $base = add_query_arg($a['page_var'], '%#%');
    $links = paginate_links([
      'base'      => $base,
      'format'    => '',
      'current'   => $paged,
      'total'     => $total_p,
      'type'      => 'array',
      'prev_text' => '« Prev',
      'next_text' => 'Next »',
    ]);
    if ( ! empty($links) ) {
      echo '<nav class="tmw-pagination" aria-label="Pagination">';
      foreach ($links as $l) echo $l;
      echo '</nav>';
    }
  }

  return ob_get_clean();
});

/**
 * Shortcode: 4 promo flipboxes with external links
 * [promo_flipboxes
 *   front1="" back1="" url1="" title1=""
 *   front2="" back2="" url2="" title2=""
 *   front3="" back3="" url3="" title3=""
 *   front4="" back4="" url4="" title4=""
 *   cols="4"]
 */
add_shortcode('promo_flipboxes', function($atts){
  $a = shortcode_atts([
    'front1'=>'','back1'=>'','url1'=>'','title1'=>'',
    'front2'=>'','back2'=>'','url2'=>'','title2'=>'',
    'front3'=>'','back3'=>'','url3'=>'','title3'=>'',
    'front4'=>'','back4'=>'','url4'=>'','title4'=>'',
    'cols'=>4,
  ], $atts);

  $items = [];
  for ($i=1;$i<=4;$i++){
    $front = $a["front{$i}"]; $back = $a["back{$i}"]; $url = $a["url{$i}"]; $title = $a["title{$i}"];
    if ( !empty($url) && (!empty($front) || !empty($back)) ){
      $items[] = compact('front','back','url','title');
    }
  }
  if ( empty($items) ) return '';

  ob_start();
  printf('<div class="tmw-grid tmw-cols-%d">', (int)$a['cols']);
  foreach ($items as $it){
    $front = esc_url($it['front']); $back = esc_url($it['back'] ?: $it['front']);
    $url   = esc_url($it['url']);   $title= esc_html($it['title'] ?: 'Open');
    echo '<a class="tmw-flip" href="'.$url.'" target="_blank" rel="sponsored noopener nofollow">
            <div class="tmw-flip-inner">
              <div class="tmw-flip-front" style="background-image:url('.$front.');">
                <span class="tmw-name">'.$title.'</span>
              </div>
              <div class="tmw-flip-back" style="background-image:url('.$back.');">
                <span class="tmw-view">Go</span>
              </div>
            </div>
          </a>';
  }
  echo '</div>';
  return ob_get_clean();
});
/**
 * Performance: preload child CSS and improve LCP on the first image.
 * - Preloads our child CSS (rt-child-flip) non-blocking and switches to stylesheet onload
 * - Makes the first above-the-fold image eager/high priority
 * - Small JS fallback for non-attachment images inside grids
 */
add_filter('style_loader_tag', function( $html, $handle, $href, $media ) {
  if ( 'rt-child-flip' === $handle ) {
    $href  = esc_url( $href );
    $media = esc_attr( $media ?: 'all' );
    // Preload + noscript fallback
    return "<link rel='preload' as='style' href='{$href}' media='{$media}' onload=\"this.onload=null;this.rel='stylesheet'\" />"
         . "<noscript><link rel='stylesheet' href='{$href}' media='{$media}' /></noscript>";
  }
  return $html;
}, 10, 4);

add_filter('wp_get_attachment_image_attributes', function( $attr ) {
  static $tmw_first = false;
  if ( ! $tmw_first && ( is_front_page() || is_home() || is_archive() ) ) {
    $attr['loading']       = 'eager';
    $attr['fetchpriority'] = 'high';
    $attr['decoding']      = 'async';
    $tmw_first             = true;
  }
  return $attr;
}, 20);

// Fallback for non-attachment grid images (first card only).
add_action('wp_head', function () { ?>
  <script>
  document.addEventListener('DOMContentLoaded',function(){
    var img = document.querySelector('.video-grid img, .tmw-grid img');
    if(img){
      img.setAttribute('loading','eager');
      img.setAttribute('fetchpriority','high');
      img.setAttribute('decoding','async');
    }
  });
  </script>
<?php }, 99);
