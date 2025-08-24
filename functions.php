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

/* ---------------- AWE: Admin purge button + helpers ---------------- */

if (!function_exists('tmw_normalize_nick')) {
  function tmw_normalize_nick($s){
    $s = strtolower($s);
    $s = preg_replace('~[^\pL\d]+~u', '', $s); // remove spaces, hyphens, underscores
    return $s;
  }
}

if (!function_exists('tmw_aw_get_feed')) {
  function tmw_aw_get_feed($ttl_minutes = 10) {
    $key = 'tmw_aw_feed_v1';
    $cached = get_transient($key);
    if ($cached !== false) return $cached;

    if (!defined('AWEMPIRE_FEED_URL') || !AWEMPIRE_FEED_URL) return [];

    $resp = wp_remote_get(AWEMPIRE_FEED_URL, ['timeout' => 15]);
    if (is_wp_error($resp)) return [];

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (!is_array($data)) $data = [];
    set_transient($key, $data, $ttl_minutes * MINUTE_IN_SECONDS);
    return $data;
  }
}

if (!function_exists('tmw_aw_find_by_candidates')) {
  function tmw_aw_find_by_candidates($cands){
    $feed = tmw_aw_get_feed();
    if (empty($feed)) return null;

    $norms = array_map('tmw_normalize_nick', array_filter(array_unique($cands)));
    foreach ($feed as $row){
      $nick = strtolower($row['nickname'] ?? ($row['name'] ?? ''));
      if (!$nick) continue;
      if (in_array(tmw_normalize_nick($nick), $norms, true)) {
        return $row;
      }
    }
    return null;
  }
}

if (!function_exists('tmw_aw_pick_images_from_row')) {
  function tmw_aw_pick_images_from_row($row) {
    $all = [];

    // Crawl any nested arrays for image URLs
    $walk = function($v) use (&$walk, &$all) {
      if (is_string($v) && preg_match('~https?://[^\s"]+\.(?:jpe?g|png|webp)(?:\?[^\s"]*)?$~i', $v)) {
        $all[] = $v;
      } elseif (is_array($v)) {
        foreach ($v as $vv) $walk($vv);
      }
    };
    $walk($row);

    $front = $back = null;
    foreach ($all as $u) { if (strpos($u, '800x600') !== false) { $front = $u; break; } }
    foreach ($all as $u) { if (strpos($u, '896x504') !== false) { $back  = $u; break; } }

    if (!$front) $front = $all[0] ?? null;
    if (!$back)  $back  = $all[1] ?? $front;

    return [$front, $back];
  }
}

if (!function_exists('tmw_aw_build_link')) {
  function tmw_aw_build_link($base, $sub = '') {
    if (!$base) return '#';
    if ($sub) {
      if (strpos($base, '{SUBAFFID}') !== false) {
        return str_replace('{SUBAFFID}', rawurlencode($sub), $base);
      }
      $sep = (strpos($base, '?') !== false) ? '&' : '?';
      return $base . $sep . 'subAffId=' . rawurlencode($sub);
    }
    return str_replace('{SUBAFFID}', '', $base);
  }
}

if (!function_exists('tmw_aw_card_data')) {
  /**
   * Returns ['front','back','link'] for a term.
   * - ACF overrides win (if present)
   * - else AWE feed auto-match by nickname/name/slug/flattened
   * - link is the *AWE tracking* (used on biography promo cards),
   *   but on the models grid we’ll still use term link.
   */
  function tmw_aw_card_data($term_id) {
    $placeholder = get_stylesheet_directory_uri() . '/assets/img/placeholders/model-card.jpg';

    $front = $back = '';
    // 1) ACF overrides
    if (function_exists('get_field')) {
      $acf_front = get_field('actor_card_front', 'actors_' . $term_id);
      $acf_back  = get_field('actor_card_back',  'actors_' . $term_id);
      if (is_array($acf_front) && !empty($acf_front['url'])) $front = $acf_front['url'];
      if (is_array($acf_back)  && !empty($acf_back['url']))  $back  = $acf_back['url'];
    }

    // 2) AWE feed auto-match
    $term = get_term($term_id);
    $cands = [];
    $explicit = get_term_meta($term_id, 'tmw_aw_nick', true);
    if (!$explicit) $explicit = get_term_meta($term_id, 'tm_lj_nick', true); // legacy key
    if ($explicit) $cands[] = $explicit;

    if ($term && !is_wp_error($term)) {
      $cands[] = $term->slug;                    // abby-murray
      $cands[] = $term->name;                    // Abby Murray
      $cands[] = str_replace(['-','_',' '], '', $term->slug); // abbymurray
      $cands[] = str_replace(['-','_',' '], '', $term->name); // aellenagrace
    }

    $row = tmw_aw_find_by_candidates(array_unique(array_filter($cands)));

    // Build link (used on biography promo cards)
    $sub = get_term_meta($term_id, 'tmw_aw_subaff', true);
    if (!$sub) $sub = get_term_meta($term_id, 'tm_subaff', true); // legacy key
    if (!$sub && $term && !is_wp_error($term)) $sub = $term->slug;
    $link = '';

    if ($row) {
      if (!$front || !$back) {
        list($f, $b) = tmw_aw_pick_images_from_row($row);
        if (!$front) $front = $f;
        if (!$back)  $back  = ($b ?: $front);
      }
      $link = tmw_aw_build_link(($row['tracking_url'] ?? ($row['url'] ?? '')), $sub ?: ($explicit ?: ($term->slug ?? '')));
    }

    if (!$front) $front = $placeholder;
    if (!$back)  $back  = $front;

    return ['front' => $front, 'back' => $back, 'link' => $link];
  }
}

/* Admin bar button to purge feed cache */
add_action('admin_bar_menu', function($bar){
  if (!current_user_can('manage_options')) return;
  $bar->add_node([
    'id'    => 'tmw_aw_clear_cache',
    'title' => 'Purge AWEmpire Cache',
    'href'  => wp_nonce_url(admin_url('?tmw_aw_clear_cache=1'), 'tmw_aw_clear_cache'),
  ]);
}, 100);

add_action('admin_init', function(){
  if (
    current_user_can('manage_options') &&
    isset($_GET['tmw_aw_clear_cache']) &&
    wp_verify_nonce($_GET['_wpnonce'] ?? '', 'tmw_aw_clear_cache')
  ) {
    delete_transient('tmw_aw_feed_v1');
    wp_safe_redirect(remove_query_arg(['tmw_aw_clear_cache','_wpnonce']));
    exit;
  }
});
/* ---------------- end AWE helpers ---------------- */

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

// === MODELS/ACTORS GRID (internal links) ===
add_shortcode('actors_flipboxes', function($atts){
  $a = shortcode_atts([
    'per_page'       => 16,
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
    // NEW: where to source images from: auto|aw|acf
    'img_source'     => 'auto',
  ], $atts);

  // Current page
  $paged = 1;
  if ( isset($_GET[$a['page_var']]) ) {
    $paged = max(1, intval($_GET[$a['page_var']]));
  } else {
    $paged = max(1, intval(get_query_var('paged')), intval(get_query_var('page')));
  }

  $per_page   = max(1, intval($a['per_page']));
  $offset     = ($paged - 1) * $per_page;
  $hide_empty = filter_var($a['hide_empty'], FILTER_VALIDATE_BOOLEAN);

  $terms = get_terms([
    'taxonomy'   => 'actors',
    'hide_empty' => $hide_empty,
    'orderby'    => $a['orderby'],
    'order'      => $a['order'],
    'number'     => $per_page,
    'offset'     => $offset,
  ]);
  if ( is_wp_error($terms) ) return '';

  // Count for pagination
  $total = tmw_count_terms('actors', $hide_empty);
  $total_p = max(1, (int)ceil($total / $per_page));

  ob_start();

  // Optional title bar (keep if you had it)
  // echo '<div class="tmw-title"><span class="tmw-star">★</span>Actors</div>';

  printf('<div class="tmw-grid tmw-cols-%d">', (int)$a['cols']);

  $i = 0;
  foreach ($terms as $term){
    // Link to the model term page stays the same
    $link = get_term_link($term);

    // 1) Defaults (so we always have something)
    $front_url = '';
    $back_url  = '';

    // 2) Use AWE helper if present
    if ( function_exists('tmw_aw_card_data') ) {
      $card = tmw_aw_card_data($term->term_id); // uses AWEMPIRE_FEED_URL + caching
      if ( !empty($card['front']) ) $front_url = $card['front'];
      if ( !empty($card['back'])  ) $back_url  = $card['back'];
    }

    // 3) Fall back to ACF if needed
    if ( (empty($front_url) || empty($back_url)) && function_exists('get_field') ) {
      $acf_front = get_field('actor_card_front', 'actors_'.$term->term_id);
      $acf_back  = get_field('actor_card_back',  'actors_'.$term->term_id);
      if (empty($front_url) && is_array($acf_front) && !empty($acf_front['url'])) $front_url = $acf_front['url'];
      if (empty($back_url)  && is_array($acf_back)  && !empty($acf_back['url']))  $back_url  = $acf_back['url'];
    }

    // 4) Final fallback to placeholder
    if ( empty($front_url) ) {
      $front_url = get_stylesheet_directory_uri().'/assets/img/placeholders/model-card.jpg';
    }
    if ( empty($back_url) ) {
      $back_url  = $front_url;
    }

    // Output card
    echo '<a class="tmw-flip" href="'.esc_url($link).'" aria-label="'.esc_attr($term->name).'">';
    echo   '<div class="tmw-flip-inner">';
    echo     '<div class="tmw-flip-front" style="background-image:url('.esc_url($front_url).');">';
      echo       '<span class="tmw-name">'.esc_html($term->name).'</span>';
      echo     '</div>';
    echo     '<div class="tmw-flip-back" style="background-image:url('.esc_url($back_url).');">';
    echo       '<span class="tmw-view">View profile &raquo;&raquo;&raquo;</span>';
      echo     '</div>';
      echo   '</div>';
      echo '</a>';

    $i++;
    // Banner after 8th item (unchanged)
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
      if ( !empty($banner_html) ) {
        echo '<div class="tmw-banner-wrap">'.$banner_html.'</div>';
      }
    }
  }
  echo '</div>'; // .tmw-grid

  // Pagination (unchanged)
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

// Unified flipbox tap/click behavior (mobile-friendly)
add_action('wp_footer', function(){ ?>
  <script>
  (function(){
    function closest(el, sel){ return el && el.closest ? el.closest(sel) : null; }

    // Heuristic for mobile/touch
    var isMobile = false;
    try {
      isMobile = (matchMedia('(pointer: coarse)').matches || matchMedia('(hover: none)').matches);
    } catch(e) {
      // fallback width check
      isMobile = (window.innerWidth <= 992);
    }

    function onInteract(e){
      var card = closest(e.target, 'a.tmw-flip');
      if(!card) return; // not a flipbox card

      var onLabel = !!closest(e.target, '.tmw-view');   // the back-side label/button
      var isPromo = card.classList.contains('tmw-promo'); // external promo cards under bio

      if(isPromo){
        // PROMO CARDS (external): only the back label navigates, always new tab
        if(onLabel){
          e.preventDefault();
          window.open(card.href, '_blank', 'noopener');
        }else{
          // tap/click elsewhere: just flip, no navigation
          e.preventDefault();
        }
        return;
      }

      // INTERNAL ACTOR GRID CARDS
      if(isMobile){
        // On mobile: only back label should navigate (same tab)
        if(onLabel){
          // allow default navigation
          return;
        }else{
          // tap on front/elsewhere: prevent navigation (flip only)
          e.preventDefault();
          return;
        }
      }
      // Desktop: do nothing (default behavior ok)
    }

    // Use capture to catch early and be robust against theme handlers
    document.addEventListener('click', onInteract, true);
    document.addEventListener('touchend', onInteract, true);
  })();
  </script>
<?php }, 50);

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

/**
 * Remove video embeds/shortcodes from post content (description) on single posts.
 * Prevents the video from appearing twice (theme player + content).
 */
add_filter('the_content', function ($content) {
    // Only affect single posts (Retrotube uses "post" for videos)
    if (!is_singular('post')) {
        return $content;
    }

    // 1) Remove common video shortcodes (standalone or enclosed)
    $shortcodes = [
        'video', 'playlist', 'audio', 'embed', 'wpvideo', 'wp_playlist',
        'jwplayer', 'videojs', 'wps_player', 'plyr', 'wp-script-player'
    ];
    foreach ($shortcodes as $tag) {
        // [tag ...]...[/tag]
        $content = preg_replace('/\\[' . $tag . '[^\\]]*\\].*?\\[\\/' . $tag . '\\]/is', '', $content);
        // Self-closing: [tag ...]
        $content = preg_replace('/\\[' . $tag . '[^\\]]*\\]/is', '', $content);
    }

    // 2) Strip raw HTML embeds
    // <iframe ...></iframe>
    $content = preg_replace('/<iframe\\b[^>]*>.*?<\\/iframe>/is', '', $content);
    // <video ...></video>
    $content = preg_replace('/<video\\b[^>]*>.*?<\\/video>/is', '', $content);
    // <embed ...>
    $content = preg_replace('/<embed\\b[^>]*>/is', '', $content);
    // WordPress block/figure wrappers for embeds
    $content = preg_replace('/<figure[^>]*class="[^"]*wp-block-embed[^"]*"[^>]*>.*?<\\/figure>/is', '', $content);
    $content = preg_replace('/<div[^>]*class="[^"]*(wp-video|video-player)[^"]*"[^>]*>.*?<\\/div>/is', '', $content);

    return $content;
}, 20);

// Disable WordPress auto-embeds (e.g. raw URLs turned into players)
remove_filter('the_content', array($GLOBALS['wp_embed'], 'autoembed'), 8);

/**
 * Strip any video inside post content on single video posts.
 * Multi-layer approach:
 *  - block video shortcodes before they render
 *  - kill oEmbed output
 *  - remove Gutenberg video/embed blocks
 *  - final HTML sweep to remove <iframe>/<video>/<embed>
 * The theme's main player (outside the_content) is unaffected.
 */

if (!function_exists('tmw_strip_video_in_content_active')) {
  function tmw_strip_video_in_content_active(): bool {
    // Adjust post type list if your videos use a custom type
    if (is_admin()) return false;
    if (!is_singular()) return false;
    $pt = get_post_type();
    $video_types = ['post','video','videos','wpsc-video','wp-script-video','wpws_video'];
    return in_array($pt, $video_types, true);
  }
}

// 1) Stop video shortcodes before they execute
add_filter('pre_do_shortcode_tag', function($return, $tag, $atts, $m){
  if (!tmw_strip_video_in_content_active()) return $return;
  $video_tags = [
    'video','playlist','audio','embed','wpvideo','wp_playlist',
    'youtube','vimeo','dailymotion','jwplayer','videojs','fvplayer','plyr',
    'wpsc_video','wps_video','wpws_video','flowplayer','jetpack_video'
  ];
  if (in_array(strtolower($tag), $video_tags, true)) {
    return ''; // block it
  }
  return $return;
}, 10, 4);

// 2) Kill oEmbed output inside content
add_filter('embed_oembed_html', function($html, $url, $attr, $post_ID){
  if (!tmw_strip_video_in_content_active()) return $html;
  return ''; // no embedded players in content area
}, 10, 4);

add_filter('oembed_dataparse', function($return, $data, $url){
  if (!tmw_strip_video_in_content_active()) return $return;
  return ''; // belt & suspenders
}, 10, 3);

// 3) Remove Gutenberg video/embed blocks when rendering content
add_filter('render_block', function($block_content, $block){
  if (!tmw_strip_video_in_content_active()) return $block_content;
  $name = isset($block['blockName']) ? $block['blockName'] : '';
  if (!$name) return $block_content;

  // core/video, core/embed and all core-embed/* (YouTube, Vimeo, etc.)
  if ($name === 'core/video' || $name === 'core/embed' || strpos($name, 'core-embed/') === 0) {
    return '';
  }
  return $block_content;
}, 9, 2);

// 4) Final HTML sweep on the_content (removes raw tags & wrappers)
add_filter('the_content', function ($content) {
  if (!tmw_strip_video_in_content_active()) return $content;

  $patterns = [
    '#<iframe\\b[^>]*>.*?</iframe>#is',
    '#<video\\b[^>]*>.*?</video>#is',
    '#<audio\\b[^>]*>.*?</audio>#is',
    '#<object\\b[^>]*>.*?</object>#is',
    '#<embed\\b[^>]*>.*?</embed>#is',
    // common wrappers/classes used by blocks/players
    '#<figure[^>]*class="[^\"]*(wp-block-embed|wp-block-video)[^\"]*"[^>]*>.*?</figure>#is',
    '#<div[^>]*class="[^\"]*(wp-block-embed|video-js|jwplayer|plyr|flowplayer|responsive-embed|embed-container)[^\"]*"[^>]*>.*?</div>#is',
  ];
  foreach ($patterns as $rx) {
    $content = preg_replace($rx, '', $content);
  }

  // Generic shortcode cleanup if any slipped through
  $content = preg_replace('/\\[[^\\]]*?video[^\\]]*\\](?:.*?\\[\\/[^\\]]*?video[^\\]]*\\])?/is', '', $content);
  // Remove empty <p> left behind
  $content = preg_replace('/<p>\\s*<\\/p>/i', '', $content);

  return $content;
}, 99);

/**
 * Pick front/back images for a model/actor term.
 * $prefer: 'auto' (default) | 'aw' | 'acf'
 * - 'auto': use AWEmpire if available (tmw_aw_card_data), else ACF (actor_card_front/back), else term thumbnail.
 * - 'aw':   force AWEmpire (falls back to ACF if missing).
 * - 'acf':  force ACF/thumbnail only.
 *
 * @return array [front_url, back_url]
 */
function tmw_get_term_images( int $term_id, string $prefer = 'auto' ): array {
  $front = ''; $back = '';

  $use_aw  = ($prefer === 'aw'  || $prefer === 'auto');
  $use_acf = ($prefer === 'acf' || $prefer === 'auto');

  // 1) AWEmpire (if bridge loaded and nickname configured)
  if ( $use_aw && function_exists('tmw_aw_card_data') ) {
    $cd = tmw_aw_card_data($term_id);
    if ( !empty($cd['front']) ) $front = esc_url_raw($cd['front']);
    if ( !empty($cd['back'])  ) $back  = esc_url_raw($cd['back']);
  }

  // 2) ACF fallbacks (actor_card_front/back)
  if ( $use_acf && (!$front || !$back) && function_exists('get_field') ) {
    $acf_id = 'actors_'.$term_id;
    $acf_front = get_field('actor_card_front', $acf_id);
    $acf_back  = get_field('actor_card_back',  $acf_id);
    if (!$front && is_array($acf_front) && !empty($acf_front['url'])) $front = esc_url_raw($acf_front['url']);
    if (!$back  && is_array($acf_back)  && !empty($acf_back['url']))   $back  = esc_url_raw($acf_back['url']);
  }

  // 3) Taxonomy thumbnail as a last resort
  if (!$front || !$back) {
    $thumb_id = (int) get_term_meta($term_id, 'thumbnail_id', true);
    if ($thumb_id) {
      $thumb_url = wp_get_attachment_image_url($thumb_id, 'large');
      if (!$front) $front = $thumb_url ?: '';
      if (!$back)  $back  = $thumb_url ?: '';
    }
  }

  // Ensure back isn’t empty
  if (!$back) $back = $front;

  return [$front, $back];
}


/**
 * Gap fix: hide duplicate About player + clean spacing.
 */
function tmw_gap_fix_assets() {
  $ver = wp_get_theme( get_stylesheet() )->get( 'Version' ); // child theme version
  $base = get_stylesheet_directory_uri();

  wp_enqueue_style(
    'tmw-gap-fix',
    $base . '/assets/css/tmw-gap-fix.css',
    array(),
    $ver
  );

  // optional JS cleanup
  wp_enqueue_script(
    'tmw-gap-fix',
    $base . '/assets/js/tmw-gap-fix.js',
    array(),
    $ver,
    true
  );
}
add_action( 'wp_enqueue_scripts', 'tmw_gap_fix_assets', 99 );

