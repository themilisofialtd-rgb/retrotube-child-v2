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
 *             trigger_text="Watch now" banner_img="" banner_url="" banner_alt=""
 *             show_pagination="true" page_var="pg"]
 */
add_shortcode('actors_flipboxes', function($atts){
  $a = shortcode_atts([
    'per_page'       => 16,
    'cols'           => 4,
    'orderby'        => 'name',
    'order'          => 'ASC',
    'hide_empty'     => false,
    'trigger_text'   => 'Watch now',
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
  $trigger    = sanitize_text_field($a['trigger_text']);

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
    // Advanced Custom Fields (optional)
    $front = function_exists('get_field') ? get_field('actor_card_front', 'actors_'.$term->term_id) : null;
    $back  = function_exists('get_field') ? get_field('actor_card_back',  'actors_'.$term->term_id) : null;
    $front_url = is_array($front) && !empty($front['url']) ? $front['url'] : '';
    $back_url  = is_array($back)  && !empty($back['url'])  ? $back['url']  : $front_url;

    $link = get_term_link($term);
    echo '<a class="tmw-flip" href="'.esc_url($link).'" aria-label="'.esc_attr($term->name).'">
            <div class="tmw-flip-inner">
              <div class="tmw-flip-front" style="background-image:url('.esc_url($front_url).');">
                <span class="tmw-name">'.esc_html($term->name).'</span>
                <span class="tmw-trigger">'.esc_html($trigger).'</span>
              </div>
              <div class="tmw-flip-back" style="background-image:url('.esc_url($back_url).');">
                <span class="tmw-view">View profile</span>
              </div>
            </div>
          </a>';

    $i++;
    // Inject banner after 8th item
    if ( $i === 8 ) {
      $banner_html = '';
      if ( !empty($a['banner_html']) ) {
        $banner_html = $a['banner_html'];
      } else if ( !empty($a['banner_img']) && !empty($a['banner_url']) ) {
        $banner_html = '<a class="tmw-banner" href="'.esc_url($a['banner_url']).'" target="_blank" rel="sponsored nofollow noopener">
                          <img src="'.esc_url($a['banner_img']).'" alt="'.esc_attr($a['banner_alt']).'" width="364" height="45">
                        </a>';
      } else {
        // Try external file: /assets/models-banner.html (editable by user)
        $banner_file = get_stylesheet_directory() . '/assets/models-banner.html';
        if ( is_readable($banner_file) ) {
          $banner_html = file_get_contents($banner_file);
        }
      }
      if ( !empty($banner_html) ) {
        echo '<div class="tmw-banner-wrap">'.$banner_html.'</div>';
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
                <span class="tmw-trigger">Open</span>
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
 * Preload child CSS (non-render-blocking) and then switch to stylesheet.
 *
 * @param string $html   The link tag for the enqueued style.
 * @param string $handle The style's registered handle.
 * @param string $href   The stylesheet's source URL.
 * @param string $media  The media for which this stylesheet has been defined.
 * @return string Modified <link> tag.
 */
add_filter('style_loader_tag', function( $html, $handle, $href, $media ) {
  if ( 'rt-child-flip' === $handle ) {
    $href  = esc_url( $href );
    $media = esc_attr( $media ?: 'all' );
    return "<link rel='preload' as='style' href='{$href}' media='{$media}' onload=\"this.onload=null;this.rel='stylesheet'\">";
  }
  return $html;
}, 10, 4);

/**
 * Make the first above-the-fold image eager & high priority to improve LCP.
 * Works for featured images; a small JS fallback handles plain <img> in grids.
 *
 * @param array $attr Attributes for the image markup.
 * @return array Possibly modified attributes.
 */
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
