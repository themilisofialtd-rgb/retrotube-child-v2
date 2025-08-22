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

// Rename "Video Actors" taxonomy labels to "Models"
add_filter('register_taxonomy_args', function($args, $taxonomy){
  if ($taxonomy !== 'actors') return $args;
  $labels = isset($args['labels']) ? $args['labels'] : [];
  $labels['name']                       = 'Models';
  $labels['singular_name']              = 'Model';
  $labels['menu_name']                  = 'Models';
  $labels['all_items']                  = 'All Models';
  $labels['search_items']               = 'Search Models';
  $labels['popular_items']              = 'Popular Models';
  $labels['edit_item']                  = 'Edit Model';
  $labels['view_item']                  = 'View Model';
  $labels['update_item']                = 'Update Model';
  $labels['add_new_item']               = 'Add New Model';
  $labels['new_item_name']              = 'New Model Name';
  $labels['separate_items_with_commas'] = 'Separate models with commas';
  $labels['add_or_remove_items']        = 'Add or remove models';
  $labels['choose_from_most_used']      = 'Choose from the most used models';
  $labels['not_found']                  = 'No models found';
  $args['labels'] = $labels;
  $args['label']  = $labels['name'];
  return $args;
}, 10, 2);

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

// Landscape hero for model biography (retina-ready for ~720px display width)
add_action('after_setup_theme', function () {
  add_image_size('tmw-actor-hero-land', 1440, 810, true); // 16:9 hard crop
});

// Horizontal hero banner for model biography (4:1)
add_action('after_setup_theme', function () {
  add_image_size('tmw-actor-hero-banner', 1200, 300, true); // hard crop 1200×300
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
              </div>';
    // Back label; the entire card (including this text) links to the model's biography via $link.
    echo '  <div class="tmw-flip-back" style="background-image:url('.esc_url($back_url).');">
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

// Allow navigation on promo cards ONLY when the back label is clicked
add_action('wp_footer', function(){ ?>
  <script>
  (function(){
    function closest(el, sel){ return el && el.closest ? el.closest(sel) : null; }

    function onClick(e){
      var promoA  = closest(e.target, 'a.tmw-flip.tmw-promo');
      if(!promoA) return; // not a promo card

      var onLabel = !!closest(e.target, '.tmw-view'); // the back-side action label
      if(onLabel){
        // Always open external in a new tab (mobile/desktop)
        e.preventDefault();
        window.open(promoA.href, '_blank', 'noopener');
      }else{
        // Click elsewhere on the card (front or back background): just flip, no navigation
        e.preventDefault();
      }
    }

    document.addEventListener('click', onClick);
    document.addEventListener('touchend', onClick);
  })();
  </script>
<?php }, 45);

/* -----------------------------------------
 * ACF local fields: Promo Flipboxes (actors)
 * ----------------------------------------- */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) return;

  // Helper to build one promo group
  $promo_group = function ($n, $default_title = '') {
    return [
      'key'   => "field_tmw_promo_{$n}",
      'label' => "Promo {$n}",
      'name'  => "promo{$n}",
      'type'  => 'group',
      'layout'=> 'block',
      'sub_fields' => [
        [
          'key'   => "field_tmw_promo{$n}_title",
          'label' => 'Label / Text',
          'name'  => 'title',
          'type'  => 'text',
          'default_value' => $default_title,
          'wrapper' => ['width' => 40],
        ],
        [
          'key'   => "field_tmw_promo{$n}_url",
          'label' => 'External URL',
          'name'  => 'url',
          'type'  => 'url',
          'wrapper' => ['width' => 60],
        ],
        [
          'key'   => "field_tmw_promo{$n}_front",
          'label' => 'Front image',
          'name'  => 'front',
          'type'  => 'image',
          'return_format' => 'array',
          'preview_size'  => 'medium',
          'instructions'  => 'Recommended: 800×1200 (portrait) to match your flipboxes',
          'wrapper' => ['width' => 50],
        ],
        [
          'key'   => "field_tmw_promo{$n}_back",
          'label' => 'Back image (optional)',
          'name'  => 'back',
          'type'  => 'image',
          'return_format' => 'array',
          'preview_size'  => 'medium',
          'wrapper' => ['width' => 50],
        ],
      ],
    ];
  };

  acf_add_local_field_group([
    'key'    => 'group_tmw_actor_promos',
    'title'  => 'Promo Flipboxes',
    'fields' => [
      $promo_group(1, 'OnlyFans'),
      $promo_group(2, 'Fansly'),
      $promo_group(3, 'Reddit'),
      $promo_group(4, 'Website'),
    ],
    'location' => [[[
      'param'    => 'taxonomy',
      'operator' => '==',
      'value'    => 'actors',   // “Models” taxonomy
    ]]],
    'menu_order' => 30,
  ]);
});

/**
 * Front-end renderer for the 4 promo flipboxes on an actor page.
 */
function tmw_render_actor_promos($term_id){
  if (!function_exists('get_field')) return '';

  $items = [];
  for ($i=1; $i<=4; $i++){
    $g = get_field("promo{$i}", "actors_{$term_id}");
    if (!$g) continue;

    $url   = isset($g['url'])   ? trim($g['url'])   : '';
    $title = isset($g['title']) ? trim($g['title']) : ''; // this is the BACK label (your custom text)
    $front = isset($g['front']['url']) ? $g['front']['url'] : '';
    $back  = isset($g['back']['url'])  ? $g['back']['url']  : $front;

    if ($url && $front){
      $items[] = [
        'url'   => esc_url($url),
        'title' => esc_html($title ?: 'Open'),
        'front' => esc_url($front),
        'back'  => esc_url($back),
      ];
    }
  }
  if (!$items) return '';

  ob_start(); ?>
  <section class="tmw-actor-promos" aria-label="Promotions">
    <div class="tmw-grid tmw-cols-4" style="margin-top:18px">
      <?php foreach ($items as $it):
        // Anchor for promo cards — must be external/new tab
        echo '<a class="tmw-flip tmw-promo" href="'.$it['url'].'" target="_blank" rel="sponsored nofollow noopener" data-external="1">';
      ?>
          <div class="tmw-flip-inner">
            <!-- FRONT: fixed CTA -->
            <div class="tmw-flip-front" style="background-image:url('<?php echo $it['front']; ?>');">
              <span class="tmw-name">View more</span>
            </div>
            <!-- BACK: your custom per-card text -->
            <div class="tmw-flip-back" style="background-image:url('<?php echo $it['back']; ?>');">
              <span class="tmw-view"><?php echo $it['title']; ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
}
