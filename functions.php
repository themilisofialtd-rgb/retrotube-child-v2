<?php
/**
 * Retrotube Child (Flipbox Edition)
 * - Flipboxes + AWE helpers + admin tools
 * - Models virtual template with Banner (1200x350)
 * - Admin: right column on Model edit (RankMath box)
 *
 * IMPORTANT: Single PHP block. Do not add another <?php inside this file.
 */

// Toggle this to true temporarily to inspect banner resolution in logs/comments.
if (!defined('TMW_BANNER_DEBUG')) {
  define('TMW_BANNER_DEBUG', false);
}

/**
 * Unified banner resolver used by both admin preview and the front-end.
 * Priority: post-level ACF/legacy sources -> taxonomy ACF & feed helpers -> featured image fallback.
 */
if (!function_exists('tmw_resolve_model_banner_url')) {
  function tmw_resolve_model_banner_url($post_id = 0, $term_id = 0) {
    $arg_count = func_num_args();
    $original_post_id = $post_id;

    $post_id = (int) $post_id;
    $term_id = (int) $term_id;

    if ($arg_count === 1 && $term_id === 0 && $post_id && !get_post($post_id)) {
      $term_id = $post_id;
      $post_id = 0;
    }

    $banner_url = '';

    if ($post_id) {
      if (function_exists('get_field')) {
        $banner_field = get_field('banner_image', $post_id);
        if (is_array($banner_field) && !empty($banner_field['url'])) {
          $banner_url = (string) $banner_field['url'];
        } elseif (is_string($banner_field) && filter_var($banner_field, FILTER_VALIDATE_URL)) {
          $banner_url = $banner_field;
        }
      }

      if (empty($banner_url)) {
        $legacy = get_post_meta($post_id, 'banner_image', true);
        if (is_array($legacy) && !empty($legacy['url']) && filter_var($legacy['url'], FILTER_VALIDATE_URL)) {
          $banner_url = (string) $legacy['url'];
        } elseif (is_string($legacy) && filter_var($legacy, FILTER_VALIDATE_URL)) {
          $banner_url = $legacy;
        } elseif (is_numeric($legacy)) {
          $maybe = wp_get_attachment_url((int) $legacy);
          if (is_string($maybe) && filter_var($maybe, FILTER_VALIDATE_URL)) {
            $banner_url = $maybe;
          }
        }
      }

      if (empty($banner_url)) {
        $legacy_url = get_post_meta($post_id, 'banner_image_url', true);
        if (is_array($legacy_url) && !empty($legacy_url['url']) && filter_var($legacy_url['url'], FILTER_VALIDATE_URL)) {
          $banner_url = (string) $legacy_url['url'];
        } elseif (is_string($legacy_url) && filter_var($legacy_url, FILTER_VALIDATE_URL)) {
          $banner_url = $legacy_url;
        }
      }
    }

    if ($term_id === 0 && $post_id) {
      $terms = wp_get_post_terms($post_id, 'models');
      if (!is_wp_error($terms) && !empty($terms)) {
        $term_id = (int) $terms[0]->term_id;
      }
    }

    if (empty($banner_url) && $term_id) {
      $acf_id = 'models_' . $term_id;
      $source = function_exists('get_field') ? (get_field('banner_source', $acf_id) ?: 'feed') : 'feed';

      if ($source === 'url' && function_exists('get_field')) {
        $maybe_url = (string) (get_field('banner_image_url', $acf_id) ?: '');
        if ($maybe_url) {
          $banner_url = $maybe_url;
        }
      }

      if (empty($banner_url) && $source === 'upload' && function_exists('get_field')) {
        $img = get_field('banner_image', $acf_id);
        if (is_array($img) && !empty($img['url'])) {
          $banner_url = (string) $img['url'];
        }
      }

      if (empty($banner_url) && function_exists('tmw_aw_find_by_candidates')) {
        $term = get_term($term_id, 'models');
        $candidates = [];
        $nick = get_term_meta($term_id, 'tmw_aw_nick', true);
        if ($nick) {
          $candidates[] = $nick;
        }
        if ($term && !is_wp_error($term)) {
          $candidates[] = $term->name;
          $candidates[] = $term->slug;
        }
        $row = tmw_aw_find_by_candidates(array_unique(array_filter($candidates)));
        if ($row) {
          $maybe = tmw_pick_banner_from_feed_row($row);
          if ($maybe) {
            $banner_url = $maybe;
          }
        }
      }

      if (empty($banner_url) && function_exists('get_field')) {
        $img = get_field('banner_image', $acf_id);
        if (is_array($img) && !empty($img['url'])) {
          $banner_url = (string) $img['url'];
        } elseif (!$banner_url) {
          $maybe_url = get_field('banner_image_url', $acf_id);
          if (is_string($maybe_url) && filter_var($maybe_url, FILTER_VALIDATE_URL)) {
            $banner_url = $maybe_url;
          }
        }
      }

      if (empty($banner_url) && function_exists('tmw_placeholder_image_url')) {
        $banner_url = (string) tmw_placeholder_image_url();
      }
    }

    if (empty($banner_url) && $post_id && has_post_thumbnail($post_id)) {
      $banner_url = wp_get_attachment_image_url(get_post_thumbnail_id($post_id), 'full');
    }

    if (!empty($banner_url)) {
      $banner_url = set_url_scheme($banner_url, 'https');
    }

    if ((defined('WP_DEBUG') && WP_DEBUG) || TMW_BANNER_DEBUG) {
      $debug_post = $post_id ?: (int) $original_post_id;
      error_log('[TMW Banner Resolver] Model ' . $debug_post . ' term ' . $term_id . ' -> ' . ($banner_url ?: 'EMPTY'));
    }

    return $banner_url ? esc_url_raw($banner_url) : '';
  }
}

/**
 * Back-compat wrapper for existing calls.
 */
if (!function_exists('tmw_get_model_banner_url')) {
  function tmw_get_model_banner_url($post_id) {
    $banner = tmw_resolve_model_banner_url($post_id);

    if (empty($banner) && TMW_BANNER_DEBUG) {
      error_log('[TMW Banner] Post ' . $post_id . ' -> EMPTY');
    }

    return $banner;
  }
}

/**
 * Render the unified model banner markup for both the front-end and admin preview.
 *
 * @param int    $model_id Model post ID (falls back to current post when omitted).
 * @param string $context  Rendering context identifier (e.g. 'frontend' or 'backend').
 *
 * @return bool Whether the banner markup was rendered.
 */
if (!function_exists('tmw_render_model_banner')) {
  function tmw_render_model_banner($model_id = 0, $context = 'frontend') {
    if (!$model_id) {
      $model_id = get_the_ID();
    }

    $model_id = (int) $model_id;
    $context  = is_string($context) ? $context : 'frontend';
    $context  = $context ? sanitize_html_class($context) : 'frontend';

    if (!$model_id) {
      return false;
    }

    $url    = tmw_resolve_model_banner_url($model_id);
    $offset = (int) get_post_meta($model_id, '_banner_position_y', true);
    $classes = array_filter(['tmw-banner-frame', $context]);

    if ($url) {
      echo '<div class="tmw-banner-container">';
      echo '  <div class="' . esc_attr(implode(' ', $classes)) . '" style="--offset-y:' . (int) $offset . 'px">';
      echo '    <img src="' . esc_url($url) . '" alt="" />';
      echo '  </div>';
      echo '</div>';

      return true;
    }

    return false;
  }
}

/**
 * Resolve vertical offset (in px). Prefers saved slider meta, then ACF taxonomy `banner_offset_y` if present.
 */
if (!function_exists('tmw_get_model_banner_offset_y')) {
  function tmw_get_model_banner_offset_y($post_id) {
    $raw_y   = get_post_meta($post_id, '_banner_position_y', true);
    $has_meta = $raw_y !== '' && $raw_y !== null;
    $y       = $has_meta ? (int)$raw_y : 0;

    if (!$has_meta) {
      // Try taxonomy ACF offset
      $terms = wp_get_post_terms($post_id, 'models');
      if (!is_wp_error($terms) && !empty($terms) && function_exists('get_field')) {
        $term_id = (int)$terms[0]->term_id;
        $acf_y   = get_field('banner_offset_y', 'models_' . $term_id);
        if (is_numeric($acf_y)) {
          $y = (int)$acf_y;
        }
      }
    }

    // Clamp to sensible range
    if ($y < -1000) {
      $y = -1000;
    }
    if ($y > 1000) {
      $y = 1000;
    }

    return $y;
  }
}

if (!function_exists('tmw_get_model_banner_height')) {
  function tmw_get_model_banner_height($post_id) {
    $height = 350;

    $terms = wp_get_post_terms($post_id, 'models');
    if (!is_wp_error($terms) && !empty($terms) && function_exists('get_field')) {
      $term_id = (int)$terms[0]->term_id;
      $pick    = get_field('banner_height', 'models_' . $term_id);

      if (is_array($pick) && isset($pick['value'])) {
        $pick = $pick['value'];
      }

      if (is_numeric($pick) || is_string($pick)) {
        $pick = (string)$pick;
        if ($pick === '350') {
          $height = 350;
        }
      }
    }

    return $height;
  }
}

if (!function_exists('tmw_get_banner_style')) {
  function tmw_get_banner_style($offset_y = 0, $height = 350, $context = []) {
    $offset_y = is_numeric($offset_y) ? (int) $offset_y : 0;
    $height   = is_numeric($height) ? (int) $height : 350;
    if ($height <= 0) {
      $height = 350;
    }

    if (!is_array($context)) {
      if (is_numeric($context)) {
        $context = ['post_id' => (int) $context];
      } else {
        $context = [];
      }
    }

    $render_context = 'frontend';
    if (!empty($context['render_context']) && is_string($context['render_context'])) {
      $render_context = strtolower($context['render_context']);
    } elseif (is_admin()) {
      $render_context = 'admin';
    }

    $frame_ratio = 1338 / 396;
    $image_ratio = 0.0;
    $image_url   = isset($context['image_url']) ? (string) $context['image_url'] : '';
    $post_id     = isset($context['post_id']) ? (int) $context['post_id'] : 0;
    $term_id     = isset($context['term_id']) ? (int) $context['term_id'] : 0;

    if (!$post_id && function_exists('get_the_ID')) {
      $maybe_post_id = get_the_ID();
      if ($maybe_post_id) {
        $post_id = (int) $maybe_post_id;
      }
    }

    if (!$post_id && isset($GLOBALS['post']) && $GLOBALS['post'] instanceof WP_Post) {
      $post_id = (int) $GLOBALS['post']->ID;
    }

    if (!$image_url) {
      if ($post_id && function_exists('tmw_resolve_model_banner_url')) {
        $image_url = (string) tmw_resolve_model_banner_url($post_id);
      } elseif ($term_id && function_exists('tmw_resolve_model_banner_url')) {
        $image_url = (string) tmw_resolve_model_banner_url(0, $term_id);
      }
    }

    $image_width        = null;
    $image_height       = null;
    $meta_image_width   = null;
    $meta_image_height  = null;
    $used_meta_fallback = false;

    if ($image_url) {
      $attachment_id = function_exists('attachment_url_to_postid') ? attachment_url_to_postid($image_url) : 0;
      if ($attachment_id) {
        $meta = wp_get_attachment_metadata($attachment_id);
        if (is_array($meta)) {
          if (!empty($meta['width'])) {
            $meta_image_width = (int) $meta['width'];
            $image_width      = $meta_image_width;
          }
          if (!empty($meta['height'])) {
            $meta_image_height = (int) $meta['height'];
            $image_height      = $meta_image_height;
          }
        }
      }

      $size = null;
      if (function_exists('wp_getimagesize')) {
        $size = wp_getimagesize($image_url);
      } else {
        $size = @getimagesize($image_url); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
      }

      if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
        $image_width  = (int) $size[0];
        $image_height = (int) $size[1];
      } elseif ($meta_image_width && $meta_image_height) {
        $image_width        = $meta_image_width;
        $image_height       = $meta_image_height;
        $used_meta_fallback = true;
      }

      if ($image_width && $image_height) {
        $image_ratio = $image_width / $image_height;
      }
    }

    $object_fit            = 'cover';
    $ratio_within_tolerance = false;

    if ($image_ratio > 0) {
      $ratio_within_tolerance = (abs($image_ratio - $frame_ratio) <= ($frame_ratio * 0.12));
    }

    if ($image_ratio <= 0) {
      $object_fit = 'contain';
    } elseif ($ratio_within_tolerance) {
      $object_fit = 'contain';
    } elseif ($image_ratio > 0 && $image_ratio < $frame_ratio) {
      $object_fit = 'contain';
    }

    $max_offset = max(50, min(1000, abs($height)));
    if ($offset_y > $max_offset) {
      $offset_y = $max_offset;
    }
    if ($offset_y < -$max_offset) {
      $offset_y = -$max_offset;
    }

    $style_parts   = [];
    $style_parts[] = sprintf('--offset-y:%s;', $offset_y . 'px');
    $style_parts[] = '--offset-x:50%;';
    $style_parts[] = '--banner-object-fit:' . $object_fit . ';';
    $style_parts[] = '--banner-img-width:auto;';
    $style_parts[] = '--banner-img-height:100%;';
    $style_parts[] = '--banner-img-max-width:none;';
    $style_parts[] = '--banner-img-max-height:none;';
    $style_parts[] = '--banner-translate-x:0;';
    $style_parts[] = '--banner-position:static;';
    $style_parts[] = '--banner-left:auto;';

    if (($used_meta_fallback || $image_ratio <= 0) && $image_url && ((defined('WP_DEBUG') && WP_DEBUG) || TMW_BANNER_DEBUG)) {
      error_log('[TMW Banner] Ratio fallback: using contain (metadata/unavailable)');
    }

    if ((defined('WP_DEBUG') && WP_DEBUG) || TMW_BANNER_DEBUG) {
      $ratio_display = $image_ratio > 0 ? number_format((float) $image_ratio, 4) : 'n/a';
      error_log(sprintf('[TMW Banner] Rendering context: %s, ratio: %s, fit: %s', $render_context, $ratio_display, $object_fit));
    }

    return implode('', $style_parts);
  }
}

/* ======================================================================
 * ONE-TIME MIGRATIONS
 * ====================================================================== */
/**
 * Move all legacy model_bio posts into the model CPT.
 */
add_action('init', function(){
  global $wpdb;
  if (get_option('tmw_migrated_model_bio')) return;

  $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'model' WHERE post_type = 'model_bio'");

  update_option('tmw_migrated_model_bio', 1);
  flush_rewrite_rules();
}, 5);

/* ======================================================================
 * LEGACY CPT CLEANUP
 * ====================================================================== */
add_action('init', function(){
  global $wp_post_types;
  if (isset($wp_post_types['model_bio'])) {
    unset($wp_post_types['model_bio']);
  }
}, 20);

/* ======================================================================
 * MODEL CPT NORMALIZATION
 * ====================================================================== */
/**
 * Normalize 'model' CPT so breadcrumbs are correct.
 * Works even if CPT is registered by parent theme or plugin.
 */
add_filter('register_post_type_args', function ($args, $post_type) {
  if ($post_type !== 'model') return $args;

  // Labels used by theme breadcrumbs
  $args['labels']                 = isset($args['labels']) ? $args['labels'] : [];
  $args['labels']['name']         = 'Models';
  $args['labels']['menu_name']    = 'Models';
  $args['labels']['singular_name'] = isset($args['labels']['singular_name']) ? $args['labels']['singular_name'] : 'Model';
  $args['labels']['archives']     = 'Models';

  // Archive should be /models/
  // Singles should remain /model/%postname%/
  $args['has_archive'] = 'models';
  $args['rewrite'] = [
    'slug'       => 'model',
    'with_front' => false,
  ];

  // Ensure public so archive link is generated
  $args['public'] = true;

  return $args;
}, 10, 2);

/**
 * One-time flush so the new /models/ archive starts working immediately.
 */
add_action('init', function () {
  if (get_option('tmw_flushed_cpt_rewrites_models')) return;
  flush_rewrite_rules();
  update_option('tmw_flushed_cpt_rewrites_models', 1);
});

/**
 * Redirect legacy /model/ archive to /models/
 */
add_action('template_redirect', function () {
  $req = isset($_SERVER['REQUEST_URI']) ? trailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) : '';
  if ($req === '/model/' && !is_singular('model')) {
    wp_redirect(home_url('/models/'), 301);
    exit;
  }
});

/**
 * Add "Edit Models Page" link to admin bar when viewing /models/.
 */
add_action('admin_bar_menu', function ($admin_bar) {
  if (!is_post_type_archive('model') || !current_user_can('edit_pages')) return;

  $models_page = get_page_by_path('models');
  if (!$models_page instanceof WP_Post) return;

  $admin_bar->add_menu([
    'id'    => 'edit-models-page',
    'title' => 'Edit Models Page',
    'href'  => get_edit_post_link($models_page->ID),
  ]);
}, 100);

/* ======================================================================
 * MODEL TERM → CPT SYNC
 * ====================================================================== */
if (!function_exists('tmw_sync_model_term_to_post')) {
  function tmw_sync_model_term_to_post($term_id, $tt_id) {
    $term = get_term($term_id, 'models');
    if (is_wp_error($term) || !$term) return;

    $slug     = sanitize_title(isset($term->slug) ? $term->slug : $term->name);
    $title    = sanitize_text_field($term->name);
    $desc     = term_description($term_id, 'models');
    $content  = wp_strip_all_tags($desc);
    $existing = get_page_by_path($slug, OBJECT, 'model');

    if ($existing instanceof WP_Post) {
      $needs_update = false;
      $update_data  = ['ID' => $existing->ID];

      if ($title && $existing->post_title !== $title) {
        $update_data['post_title'] = $title;
        $needs_update              = true;
      }

      if ($content && $existing->post_content !== $content) {
        $update_data['post_content'] = $content;
        $needs_update                = true;
      }

      if ($needs_update) {
        $result = wp_update_post($update_data, true);
        if (!is_wp_error($result)) {
          error_log("[ModelSync] Updated CPT model for {$title} (slug: {$slug})");
        } else {
          error_log('[ModelSync] Failed to update model post for ' . $slug . ': ' . $result->get_error_message());
        }
      } else {
        error_log("[ModelSync] Model post already up to date for {$slug}");
      }

      return;
    }

    $post_id = wp_insert_post([
      'post_title'   => $title,
      'post_name'    => $slug,
      'post_content' => $content,
      'post_type'    => 'model',
      'post_status'  => 'publish',
    ]);

    if (!is_wp_error($post_id)) {
      error_log("[ModelSync] Created CPT model for term {$title} (slug: {$slug})");
    } else {
      error_log('[ModelSync] Failed to create model post for ' . $slug . ': ' . $post_id->get_error_message());
    }
  }
}

add_action('created_models', 'tmw_sync_model_term_to_post', 10, 2);
add_action('edited_models',  'tmw_sync_model_term_to_post', 10, 2);

add_action('init', function () {
  if (get_option('tmw_models_synced')) return;

  $terms = get_terms([
    'taxonomy'   => 'models',
    'hide_empty' => false,
  ]);

  if (is_wp_error($terms)) {
    error_log('[ModelSync] Failed to fetch models terms for retroactive sync: ' . $terms->get_error_message());
    return;
  }

  foreach ($terms as $term) {
    tmw_sync_model_term_to_post($term->term_id, $term->term_taxonomy_id);
  }

  update_option('tmw_models_synced', true);
  error_log('[ModelSync] Retroactive sync completed for existing taxonomy terms.');
}, 20);

/* ======================================================================
 * SAFE PLACEHOLDER (never 404s)
 * ====================================================================== */
if (!function_exists('tmw_placeholder_image_url')) {
  function tmw_placeholder_image_url() {
    $path = get_stylesheet_directory() . '/assets/img/placeholders/model-card.jpg';
    if (file_exists($path)) {
      return get_stylesheet_directory_uri() . '/assets/img/placeholders/model-card.jpg';
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="1200"><rect fill="#121212" width="100%" height="100%"/><text x="50%" y="50%" fill="#666" font-size="40" font-family="system-ui,Arial" text-anchor="middle">Model</text></svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
  }
}

/* ======================================================================
 * DISTINCT-IMAGE HELPERS (ignore size folders, queries, host)
 * ====================================================================== */
if (!function_exists('tmw_img_fingerprint')) {
  function tmw_img_fingerprint($url) {
    if (!$url) return '';
    $u = explode('?', $url, 2)[0];
    $u = preg_replace('~/(?:\d{3,4}x\d{3,4})/~', '/', $u);
    $u = preg_replace('~([-_]\d{3,4}x\d{3,4})(?=\.[a-z]+$)~i', '', $u);
    $p = @parse_url($u);
    $path = isset($p['path']) ? $p['path'] : $u;
    return strtolower($path);
  }
}
if (!function_exists('tmw_same_image')) {
  function tmw_same_image($a, $b) {
    if (!$a || !$b) return false;
    return tmw_img_fingerprint($a) === tmw_img_fingerprint($b);
  }
}

/* Preserve data: URLs in inline background-image styles */
if (!function_exists('tmw_bg_style')) {
  function tmw_bg_style($url){
    if (!$url) return '';
    $safe = (strpos($url, 'data:image') === 0) ? $url : esc_url($url);
    return 'background-image:url('. $safe .');';
  }
}

/* ======================================================================
 * EXPLICIT vs NON-EXPLICIT CLASSIFIER + PORTRAIT HELPERS
 * ====================================================================== */
if (!function_exists('tmw_is_portrait')) {
  function tmw_is_portrait($url) {
    $url = (string)$url;
    if (strpos($url, '600x800') !== false || strpos($url, '504x896') !== false) return true;
    if (strpos($url, '800x600') !== false || strpos($url, '896x504') !== false) return false;
    return false;
  }
}
if (!function_exists('tmw_classify_image')) {
  function tmw_classify_image($url) {
    $explicit_re    = defined('TMW_EXPLICIT_RE')    ? TMW_EXPLICIT_RE    : '~(explicit|nsfw|xxx|nude|naked|topless|boobs|tits|pussy|ass|anal|hard|sex|cum|dildo)~i';
    $nonexplicit_re = defined('TMW_NONEXPLICIT_RE') ? TMW_NONEXPLICIT_RE : '~(cover|poster|teaser|profile|safe|thumb|avatar|portrait)~i';
    $explicit_re    = apply_filters('tmw_explicit_regex',    $explicit_re);
    $nonexplicit_re = apply_filters('tmw_nonexplicit_regex', $nonexplicit_re);
    if (@preg_match($explicit_re, (string)$url))    return 'explicit';
    if (@preg_match($nonexplicit_re, (string)$url)) return 'safe';
    return 'unknown';
  }
}

/* ======================================================================
 * MODELS ⇄ AWE mapping UI + save + list columns
 * ====================================================================== */
add_action('models_add_form_fields', function(){
  ?>
  <div class="form-field term-group">
    <label for="tmw_aw_nick">AWE Nickname / Performer ID</label>
    <input type="text" name="tmw_aw_nick" id="tmw_aw_nick" value="" class="regular-text" />
    <p class="description">Must match <code>performerId</code>, <code>displayName</code>, or <code>nickname</code> in your AWE feed.</p>
  </div>
  <div class="form-field term-group">
    <label for="tmw_aw_subaff">AWE SubAff (optional)</label>
    <input type="text" name="tmw_aw_subaff" id="tmw_aw_subaff" value="" class="regular-text" />
    <p class="description">Used as <code>subAffId</code> ({SUBAFFID}) in tracking links.</p>
  </div>
  <?php wp_nonce_field('tmw_aw_term_meta', 'tmw_aw_term_meta_nonce'); ?>
  <?php
});
add_action('models_edit_form_fields', function($term){
  $nick = get_term_meta($term->term_id,'tmw_aw_nick',true);
  $sub  = get_term_meta($term->term_id,'tmw_aw_subaff',true);
  ?>
  <tr class="form-field">
    <th scope="row"><label for="tmw_aw_nick">AWE Nickname / Performer ID</label></th>
    <td>
      <input type="text" name="tmw_aw_nick" id="tmw_aw_nick" value="<?php echo esc_attr($nick); ?>" class="regular-text" />
      <p class="description">Must match <code>performerId</code>, <code>displayName</code>, or <code>nickname</code> in your AWE feed.</p>
      <?php wp_nonce_field('tmw_aw_term_meta', 'tmw_aw_term_meta_nonce'); ?>
    </td>
  </tr>
  <tr class="form-field">
    <th scope="row"><label for="tmw_aw_subaff">AWE SubAff (optional)</label></th>
    <td>
      <input type="text" name="tmw_aw_subaff" id="tmw_aw_subaff" value="<?php echo esc_attr($sub); ?>" class="regular-text" />
      <p class="description">Used as <code>subAffId</code> ({SUBAFFID}) in tracking links.</p>
    </td>
  </tr>
  <?php
});
add_action('created_models', 'tmw_save_models_aw_meta', 10);
add_action('edited_models',  'tmw_save_models_aw_meta', 10);
if (!function_exists('tmw_save_models_aw_meta')) {
  function tmw_save_models_aw_meta($term_id){
    if (!isset($_POST['tmw_aw_term_meta_nonce']) ||
        !wp_verify_nonce($_POST['tmw_aw_term_meta_nonce'], 'tmw_aw_term_meta')) {
      return;
    }
    if (isset($_POST['tmw_aw_nick']))   update_term_meta($term_id, 'tmw_aw_nick',   sanitize_text_field(wp_unslash($_POST['tmw_aw_nick'])));
    if (isset($_POST['tmw_aw_subaff'])) update_term_meta($term_id, 'tmw_aw_subaff', sanitize_text_field(wp_unslash($_POST['tmw_aw_subaff'])));
    delete_transient('tmw_aw_feed_v1'); // refresh cache after edits
  }
}
add_filter('manage_edit-models_columns', function($cols){
  $cols['tmw_aw_nick']   = 'AWE Nick/ID';
  $cols['tmw_aw_subaff'] = 'SubAff';
  return $cols;
});
add_filter('manage_models_custom_column', function($out, $col, $term_id){
  if ($col === 'tmw_aw_nick')   $out = esc_html(get_term_meta($term_id,'tmw_aw_nick',true));
  if ($col === 'tmw_aw_subaff') $out = esc_html(get_term_meta($term_id,'tmw_aw_subaff',true));
  return $out;
}, 10, 3);

/* ======================================================================
 * ADMIN DEBUG
 * ====================================================================== */
add_action('template_redirect', function () {
  $do_debug = isset($_GET['awdebug']) || isset($_GET['aw_diag']);
  if (!$do_debug) return;
  if (!is_user_logged_in() || !current_user_can('manage_options')) { status_header(403); exit('Forbidden'); }
  header('Content-Type: text/plain; charset=utf-8');

  echo "AWEMPIRE_FEED_URL defined: ".(defined('AWEMPIRE_FEED_URL') ? 'YES' : 'NO')."\n";
  if (defined('AWEMPIRE_FEED_URL')) {
    echo "Feed URL: ".AWEMPIRE_FEED_URL."\n";
    $resp  = wp_remote_get(AWEMPIRE_FEED_URL, ['timeout'=>15]);
    $code  = wp_remote_retrieve_response_code($resp);
    $body  = wp_remote_retrieve_body($resp);
    $json  = json_decode($body, true);

    $count = 0;
    if (is_array($json)) {
      if (isset($json['data']['models']) && is_array($json['data']['models'])) $count = count($json['data']['models']);
      elseif (isset($json['models']) && is_array($json['models']))            $count = count($json['models']);
    }
    echo "HTTP status: {$code}\n";
    echo "Decoded items: {$count}\n";
    echo "Body preview: ".substr($body, 0, 200)."\n\n";
  }

  $terms = get_terms(['taxonomy'=>'models','number'=>5,'hide_empty'=>false]);
  foreach ($terms as $t) {
    $card  = function_exists('tmw_aw_card_data') ? tmw_aw_card_data($t->term_id) : [];
    $front = $card['front'] ?? '';
    $back  = $card['back']  ?? '';
    echo "TERM: {$t->name} ({$t->slug})\n";
    echo "  front: {$front}\n";
    echo "  back : {$back}\n";
    if ($front && strpos($front, 'data:') !== 0) { $h = wp_remote_head($front); echo "  front HEAD: ".wp_remote_retrieve_response_code($h)."\n"; }
    if ($back  && strpos($back,  'data:') !== 0) { $h = wp_remote_head($back);  echo "  back  HEAD: ".wp_remote_retrieve_response_code($h)."\n"; }
    echo "\n";
  }
  exit;
});
add_action('template_redirect', function () {
  if (!isset($_GET['awfind'])) return;
  if (!is_user_logged_in() || !current_user_can('manage_options')) { status_header(403); exit('Forbidden'); }
  header('Content-Type: text/plain; charset=utf-8');

  $q = strtolower(trim((string)$_GET['awfind']));
  $feed = function_exists('tmw_aw_get_feed') ? tmw_aw_get_feed() : [];
  $out = [];
  foreach ((array)$feed as $row) {
    $vals = [
      $row['performerId']   ?? '',
      $row['displayName']   ?? '',
      $row['nickname']      ?? '',
      $row['name']          ?? '',
      $row['uniqueModelId'] ?? '',
    ];
    foreach ($vals as $v) {
      if ($v && strpos(strtolower($v), $q) !== false) {
        $picked = function_exists('tmw_aw_pick_images_from_row') ? tmw_aw_pick_images_from_row($row) : [null,null];
        $out[] = [
          'performerId' => $row['performerId'] ?? '',
          'displayName' => $row['displayName'] ?? '',
          'nickname'    => $row['nickname'] ?? '',
          'front'       => $picked[0] ?? '',
          'back'        => $picked[1] ?? '',
          'link'        => $row['tracking_url'] ?? ($row['url'] ?? ''),
        ];
        break;
      }
    }
  }

  echo "Query: {$q}\nMatches: ".count($out)."\n\n";
  foreach (array_slice($out, 0, 20) as $r) {
    echo "performerId : {$r['performerId']}\n";
    echo "displayName : {$r['displayName']}\n";
    echo "nickname    : {$r['nickname']}\n";
    echo "front       : {$r['front']}\n";
    echo "back        : {$r['back']}\n";
    echo "link        : {$r['link']}\n\n";
  }
  exit;
});

/* ======================================================================
 * STYLE HELPERS
 * ====================================================================== */
if (!function_exists('tmw_child_style_version')) {
  function tmw_child_style_version() {
    static $version = null;
    if ($version !== null) {
      return $version;
    }

    $path = get_stylesheet_directory() . '/style.css';
    if (file_exists($path)) {
      $version = filemtime($path);
    } else {
      $theme   = wp_get_theme(get_stylesheet());
      $version = $theme instanceof WP_Theme ? $theme->get('Version') : null;
    }

    return $version ?: time();
  }
}

/* ======================================================================
 * STYLES + LIGHTWEIGHT OPTIMIZATIONS
 * ====================================================================== */
add_action('wp_enqueue_scripts', function () {
  $parent_version = wp_get_theme(get_template())->get('Version');
  $child_version  = tmw_child_style_version();

  wp_enqueue_style(
    'retrotube-parent',
    get_template_directory_uri() . '/style.css',
    [],
    $parent_version
  );

  wp_enqueue_style(
    'retrotube-child-style',
    get_stylesheet_uri(),
    ['retrotube-parent'],
    $child_version
  );

  $flipboxes_path = get_stylesheet_directory() . '/assets/flipboxes.css';
  $flipboxes_ver  = file_exists($flipboxes_path) ? filemtime($flipboxes_path) : $child_version;

  wp_register_style(
    'rt-child-flip',
    get_stylesheet_directory_uri() . '/assets/flipboxes.css',
    ['retrotube-child-style'],
    $flipboxes_ver
  );

  wp_enqueue_style('rt-child-flip');

  // Trim unused assets
  wp_dequeue_style('wp-block-library');
  wp_dequeue_style('wp-block-library-theme');
  wp_dequeue_style('wc-blocks-style');
  wp_deregister_script('wp-embed');
}, 20);

add_action('wp_enqueue_scripts', function () {
  if (is_singular('model')) {
    wp_dequeue_style('retrotube-rating');
    wp_dequeue_script('retrotube-rating');
  }
}, 100);

add_filter('post_thumbnail_html', function($html){
  static $done = false;
  if (!$done && (is_home() || is_archive())) {
    $html = preg_replace('#\sloading=("|\')lazy("|\')#i', '', $html);
    $html = preg_replace('#<img\s#', '<img fetchpriority="high" decoding="async" ', $html, 1);
    $done = true;
  }
  return $html;
}, 10, 5);

add_action('wp_head', function(){
  if (!is_home() && !is_archive()) return;
  echo '<script>document.addEventListener("DOMContentLoaded",function(){var i=document.querySelector(".video-grid img, .tmw-grid img");if(i){i.setAttribute("fetchpriority","high");i.setAttribute("decoding","async");i.removeAttribute("loading");}});</script>';
});

/* === Force-remove JS-injected “Show more related videos” button on model pages === */
add_action('wp_footer', function () {
  if (!is_singular('model')) {
    return;
  }

  ?>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const removeButtons = () => {
      document
        .querySelectorAll('#load-more-related, .tmw-load-more, .show-more')
        .forEach((btn) => btn.remove());
    };

    removeButtons();

    if (!('MutationObserver' in window)) {
      return;
    }

    const observer = new MutationObserver(() => {
      removeButtons();
    });

    if (document.body) {
      observer.observe(document.body, { childList: true, subtree: true });
    }
  });
  </script>
  <?php
});

/* ======================================================================
 * TEMPLATE HELPERS
 * ====================================================================== */
if (!function_exists('tmw_try_parent_template')) {
  function tmw_try_parent_template(array $candidates): bool {
    $parent_dir = trailingslashit(get_template_directory());

    foreach ($candidates as $candidate) {
      $path = $parent_dir . ltrim($candidate, '/');
      if (!file_exists($path)) {
        continue;
      }

      ob_start();
      include $path;
      $output = ob_get_clean();

      if ($output !== false) {
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return true;
      }
    }

    return false;
  }
}

if (!function_exists('tmw_render_sidebar_layout')) {
  function tmw_render_sidebar_layout(string $context_class, callable $callback): void {
    $context_class = trim($context_class);
    $primary_class = 'content-area with-sidebar-right';
    if ($context_class !== '') {
      $primary_class .= ' ' . $context_class;
    }

    echo '<div id="content" class="site-content row">' . PHP_EOL;
    echo '  <div id="primary" class="' . esc_attr($primary_class) . '">' . PHP_EOL;
    echo '    <main id="main" class="site-main with-sidebar-right" role="main">' . PHP_EOL;

    call_user_func($callback);

    echo '    </main>' . PHP_EOL;
    echo '  </div>' . PHP_EOL;
    echo '  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">' . PHP_EOL;
    get_sidebar();
    echo '  </aside>' . PHP_EOL;
    echo '</div>' . PHP_EOL;
  }
}

/* ======================================================================
 * GRID/FLIP CSS (base 2:3 + rotate)
 * ====================================================================== */

/**
 * Override parent query mods for /videos/?filter=longest to prevent 404.
 */
function tmw_videos_page_override( $query ) {
  if ( ! is_admin() && $query->is_main_query() && is_page( 'videos' ) ) {
    // Neutralize parent meta query that causes empty results
    $query->set( 'meta_key', '' );
    $query->set( 'orderby', 'none' );
    $query->is_page = true;
    $query->is_home = false;
    $query->is_404  = false;
  }
}
add_action( 'pre_get_posts', 'tmw_videos_page_override', 20 );

/* ======================================================================
 * GLOBAL OVERRIDES: TRUE CENTERING + UNIFIED COLORS (wins against theme)
 * ====================================================================== */
add_action('wp_head', function () {
  ?>
  <style>
    :root{
      --tmw-name-color:#ffffff;
      --tmw-cta-color:#ffffff;
      --tmw-cta-bg:rgba(0,0,0,.55);
      --tmw-cta-border:rgba(255,255,255,.25);
    }

    /* Legibility gradient */
    .tmw-grid .tmw-flip-front::after,
    .tmwfm-grid .tmw-flip-front::after,
    .tmw-grid .tmw-flip-back::after,
    .tmwfm-grid .tmw-flip-back::after{
      content:""; position:absolute; left:0; right:0; bottom:0; height:45%;
      background:linear-gradient(to top, rgba(0,0,0,.55), rgba(0,0,0,0));
      pointer-events:none;
    }

    /* —— CENTER BOTH LABELS EXACTLY —— */
    .tmw-grid .tmw-name, .tmwfm-grid .tmw-name,
    .tmw-grid .tmw-view, .tmwfm-grid .tmw-view{
      position:absolute !important;
      left:50% !important;
      right:auto !important;
      transform:translateX(-50%) !important;
      bottom:1rem !important;
      max-width:calc(100% - 24px) !important;
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      text-align:center !important;
      line-height:1.2 !important;
      color:var(--tmw-name-color) !important;
      text-shadow:0 1px 2px rgba(0,0,0,.6) !important;
      font-weight:600 !important;
      padding:0 .1em;
    }

    /* CTA CHIP (same look everywhere) */
    .tmw-grid .tmw-view, .tmwfm-grid .tmw-view{
      padding:.45em .9em !important;
      border-radius:999px !important;
      background:var(--tmw-cta-bg) !important;
      color:var(--tmw-cta-color) !important;
      border:1px solid var(--tmw-cta-border) !important;
      font-weight:700 !important;
    }

    /* FEATURED grid + centered heading */
    .tmwfm-wrap{ margin:8px 0 16px; }
    .tmwfm-heading{
      margin:.2rem auto .8rem !important;
      font-size:1.15rem !important;
      line-height:1.2 !important;
      text-transform:uppercase !important;
      letter-spacing:.08em !important;
      text-align:center !important;
      display:block !important;
      width:100%;
      color:inherit;
    }
    .tmwfm-grid{ display:grid; gap:12px; grid-template-columns:repeat(4, minmax(0,1fr)); }
    @media (max-width:1200px){ .tmwfm-grid{ grid-template-columns:repeat(3, minmax(0,1fr)); } }
    @media (max-width:782px) { .tmwfm-grid{ grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width:480px) { .tmwfm-grid{ grid-template-columns:repeat(1, minmax(0,1fr)); } }
  </style>
  <?php
}, 999); // very late to beat any theme CSS

add_filter('wp_resource_hints', function($urls, $relation_type){
  if ('preconnect' === $relation_type) $urls[] = 'https://galleryn3.vcmdawe.com';
  if ('dns-prefetch' === $relation_type) $urls[] = '//galleryn3.vcmdawe.com';
  return $urls;
}, 10, 2);

add_action('after_setup_theme', function () {
  // Keep for completeness – not used directly by the banner
  add_image_size('tmw-model-hero-land', 1440, 810, true);   // 16:9
  add_image_size('tmw-model-hero-banner', 1200, 350, true); // ~3.43:1
});

/* ======================================================================
 * TMW TOOLS INTEGRATION (front/back overrides + alignment/zoom)
 * ====================================================================== */
if (!function_exists('tmw_tools_settings')) {
  function tmw_tools_settings(): array {
    $opt = get_option('tmw_mf_settings', []);
    return is_array($opt) ? $opt : [];
  }
}
if (!function_exists('tmw_get_model_keys')) {
  function tmw_get_model_keys(int $term_id): array {
    $keys = [];
    $aw = get_term_meta($term_id, 'tmw_aw_nick', true);
    $lj = get_term_meta($term_id, 'tmw_lj_nick', true);
    if ($aw) $keys[] = $aw;
    if ($lj) $keys[] = $lj;
    $t = get_term($term_id, 'models');
    if ($t && !is_wp_error($t)) { $keys[] = $t->name; $keys[] = $t->slug; }
    $norm = [];
    foreach ($keys as $k) {
      $k = trim((string)$k);
      if ($k === '') continue;
      $norm[] = strtolower($k);
      $norm[] = preg_replace('~[ _-]+~', '', strtolower($k));
    }
    $out = [];
    foreach (array_merge($keys, $norm) as $k) if ($k !== '' && !in_array($k, $out, true)) $out[] = $k;
    return $out;
  }
}
if (!function_exists('tmw_tools_pick_from_map')) {
  function tmw_tools_pick_from_map($map, array $cands) {
    if (!is_array($map) || empty($cands)) return null;
    foreach ($cands as $k) if (isset($map[$k]) && $map[$k] !== '') return $map[$k];
    $lower = []; foreach ($map as $k=>$v) $lower[strtolower((string)$k)] = $v;
    foreach ($cands as $k) { $lk = strtolower((string)$k); if (isset($lower[$lk]) && $lower[$lk] !== '') return $lower[$lk]; }
    $norm = []; foreach ($map as $k=>$v) $norm[preg_replace('~[ _-]+~','', strtolower((string)$k))] = $v;
    foreach ($cands as $k) { $nk = preg_replace('~[ _-]+~','', strtolower((string)$k)); if (isset($norm[$nk]) && $norm[$nk] !== '') return $norm[$nk]; }
    return null;
  }
}
if (!function_exists('tmw_bg_align_css')) {
  function tmw_bg_align_css($pos_percent = 50, $zoom = 1.0): string {
    $pos = max(0, min(100, (float)$pos_percent));
    $z   = max(1.0, min(2.5, (float)$zoom));
    $bgsize = ($z > 1.0) ? sprintf('%.2f%% auto', $z * 100.0) : 'cover';
    return sprintf(
      'background-position: %.2f%% 50%% !important; background-size: %s !important; --tmw-bgpos: %.2f%% 50%%; --tmw-bgsize: %s;',
      $pos, $bgsize, $pos, $bgsize
    );
  }
}
if (!function_exists('tmw_tools_overrides_for_term')) {
  function tmw_tools_overrides_for_term(int $term_id): array {
    $s      = tmw_tools_settings();
    $cands  = tmw_get_model_keys($term_id);

    $front_url = tmw_tools_pick_from_map($s['front_overrides'] ?? [], $cands);
    $back_url  = tmw_tools_pick_from_map($s['back_overrides']  ?? [], $cands);

    $pos_f = tmw_tools_pick_from_map($s['object_pos_front'] ?? [], $cands);
    $pos_b = tmw_tools_pick_from_map($s['object_pos_back']  ?? [], $cands);
    $zoom_f= tmw_tools_pick_from_map($s['zoom_front']       ?? [], $cands);
    $zoom_b= tmw_tools_pick_from_map($s['zoom_back']        ?? [], $cands);

    $css_front = tmw_bg_align_css($pos_f !== null ? $pos_f : 50, $zoom_f !== null ? $zoom_f : 1.0);
    $css_back  = tmw_bg_align_css($pos_b !== null ? $pos_b : 50, $zoom_b !== null ? $zoom_b : 1.0);

    return [
      'front_url' => is_string($front_url) ? $front_url : '',
      'back_url'  => is_string($back_url)  ? $back_url  : '',
      'css_front' => $css_front,
      'css_back'  => $css_back,
    ];
  }
}

/* ======================================================================
 * AWE FEED HELPERS
 * ====================================================================== */
if (!function_exists('tmw_normalize_nick')) {
  function tmw_normalize_nick($s){
    $s = strtolower($s);
    $s = preg_replace('~[^\pL\d]+~u', '', $s);
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
    if (is_array($data)) {
      if (isset($data['data']['models']) && is_array($data['data']['models'])) $data = $data['data']['models'];
      elseif (isset($data['models']) && is_array($data['models']))             $data = $data['models'];
    }
    if (!is_array($data)) $data = [];
    set_transient($key, $data, $ttl_minutes * MINUTE_IN_SECONDS);
    return $data;
  }
}
if (!function_exists('tmw_aw_find_by_candidates')) {
  function tmw_aw_find_by_candidates($cands){
    $feed = tmw_aw_get_feed();
    if (empty($feed) || !is_array($feed)) return null;
    $norms = array_map('tmw_normalize_nick', array_filter(array_unique((array)$cands)));
    foreach ($feed as $row){
      $vals = [];
      foreach (['performerId','displayName','nickname','name','uniqueModelId'] as $k) {
        if (!empty($row[$k])) $vals[] = $row[$k];
      }
      foreach ($vals as $v) {
        if (in_array(tmw_normalize_nick($v), $norms, true)) return $row;
      }
    }
    return null;
  }
}

/* ======================================================================
 * AWE image picker — prefer PORTRAIT. FRONT=safe, BACK=explicit
 * ====================================================================== */
if (!function_exists('tmw_try_portrait_variant')) {
  function tmw_try_portrait_variant($url) {
    $try = preg_replace_callback('~/(800x600|896x504)/~', function($m){
      return '/'.($m[1]==='800x600' ? '600x800' : '504x896').'/';
    }, $url, 1);
    if ($try && $try !== $url) return $try;
    $try2 = preg_replace_callback('~([-_])(800x600|896x504)(?=\.[a-z]+$)~i', function($m){
      return $m[1].($m[2]==='800x600' ? '600x800' : '504x896');
    }, $url, 1);
    return ($try2 && $try2 !== $url) ? $try2 : null;
  }
}
if (!function_exists('tmw_aw_pick_images_from_row')) {
  function tmw_aw_pick_images_from_row($row) {
    $all = [];
    $walk = function($v) use (&$walk, &$all) {
      if (is_string($v) && preg_match('~https?://[^\s"]+\.(?:jpe?g|png|webp)(?:\?[^\s"]*)?$~i', $v)) {
        $all[] = $v;
      } elseif (is_array($v)) {
        foreach ($v as $vv) $walk($vv);
      }
    };
    $walk($row);
    $all = array_values(array_unique($all));
    if (!$all) return [null, null];

    $by_pic = [];
    foreach ($all as $u) {
      $fp = function_exists('tmw_img_fingerprint') ? tmw_img_fingerprint($u) : $u;
      if (!isset($by_pic[$fp])) $by_pic[$fp] = [];
      $by_pic[$fp][] = $u;
    }

    $pick_best_url = function($urls){
      usort($urls, function($a,$b){
        $sa = (tmw_is_portrait($a) ? 2 : 0) + (strpos($a,'600x800')!==false ? 1 : 0) + (strpos($a,'504x896')!==false ? 1 : 0);
        $sb = (tmw_is_portrait($b) ? 2 : 0) + (strpos($b,'600x800')!==false ? 1 : 0) + (strpos($b,'504x896')!==false ? 1 : 0);
        return $sb <=> $sa;
      });
      return $urls[0];
    };

    $shots = [];
    foreach ($by_pic as $urls) $shots[] = $pick_best_url($urls);

    $portrait_safe = []; $portrait_exp = []; $portrait_unk = []; $land_any = [];
    foreach ($shots as $u) {
      $cls = tmw_classify_image($u);
      if (tmw_is_portrait($u)) {
        if     ($cls === 'safe')     $portrait_safe[] = $u;
        elseif ($cls === 'explicit') $portrait_exp[]  = $u;
        else                         $portrait_unk[]  = $u;
      } else {
        $land_any[] = $u;
      }
    }

    $front = $portrait_safe[0] ?? ($portrait_unk[0] ?? null);
    $back  = null;
    foreach ($portrait_exp as $u) { if (!tmw_same_image($u, $front)) { $back = $u; break; } }
    if (!$back) { foreach ($portrait_unk as $u) { if (!tmw_same_image($u, $front)) { $back = $u; break; } } }

    if (!$front && !empty($land_any)) {
      $front_try = tmw_try_portrait_variant($land_any[0]);
      $front = $front_try ? $front_try : $land_any[0];
    }
    if (!$back && !empty($land_any)) {
      foreach ($land_any as $u) {
        if (!tmw_same_image($u, $front)) {
          $back_try = tmw_try_portrait_variant($u);
          $back = $back_try ? $back_try : $u;
          break;
        }
      }
    }

    if (!$front && !$back && !empty($shots)) $front = $shots[0];
    if (!$back) $back = $front;

    return [$front, $back];
  }
}
if (!function_exists('tmw_aw_build_link')) {
  function tmw_aw_build_link($base, $sub = '') {
    if (!$base) return '#';
    if ($sub) {
      if (strpos($base, '{SUBAFFID}') !== false) return str_replace('{SUBAFFID}', rawurlencode($sub), $base);
      $sep = (strpos($base, '?') !== false) ? '&' : '?';
      return $base . $sep . 'subAffId=' . rawurlencode($sub);
    }
    return str_replace('{SUBAFFID}', '', $base);
  }
}

/* ======================================================================
 * CARD DATA
 * ====================================================================== */
if (!function_exists('tmw_aw_card_data')) {
  function tmw_aw_card_data($term_id) {
    $placeholder = tmw_placeholder_image_url();
    $front = $back = ''; $link  = '';

    // 1) ACF overrides (taxonomy: models)
    if (function_exists('get_field')) {
      $acf_front = get_field('actor_card_front', 'models_' . $term_id); // keeping field names for data continuity
      $acf_back  = get_field('actor_card_back',  'models_' . $term_id);
      if (is_array($acf_front) && !empty($acf_front['url'])) $front = $acf_front['url'];
      if (is_array($acf_back)  && !empty($acf_back['url']))  $back  = $acf_back['url'];
    }

    // 2) Candidate keys
    $term = get_term($term_id, 'models');
    $cands = [];
    $explicit = get_term_meta($term_id, 'tmw_aw_nick', true);
    if (!$explicit) $explicit = get_term_meta($term_id, 'tm_lj_nick', true);
    if ($explicit) $cands[] = $explicit;
    if ($term && !is_wp_error($term)) {
      $cands[] = $term->slug;
      $cands[] = $term->name;
      $cands[] = str_replace(['-','_',' '], '', $term->slug);
      $cands[] = str_replace(['-','_',' '], '', $term->name);
    }

    // 3) Find feed row
    $row = tmw_aw_find_by_candidates(array_unique(array_filter($cands)));
    $sub = get_term_meta($term_id, 'tmw_aw_subaff', true);
    if (!$sub && $term && !is_wp_error($term)) $sub = $term->slug;

    if ($row) {
      if (!$front || !$back) {
        list($f, $b) = tmw_aw_pick_images_from_row($row);
        if (!$front) $front = $f;
        if (!$back)  $back  = $b;
      }
      $link = tmw_aw_build_link(($row['tracking_url'] ?? ($row['url'] ?? '')), $sub ?: ($explicit ?: ($term ? $term->slug : '')));
    }

    // 4) Fallback
    if (!$front || !$back) {
      $feed = tmw_aw_get_feed();
      if (is_array($feed) && !empty($feed)) {
        $ix = crc32((string)$term_id) % count($feed);
        $alt = $feed[$ix];
        list($f2, $b2) = tmw_aw_pick_images_from_row($alt);
        if (!$front) $front = $f2 ?: $placeholder;
        if (!$back)  $back  = $b2 ?: $front;
        if (!$link)  $link  = tmw_aw_build_link(($alt['tracking_url'] ?? ($alt['url'] ?? '')), $sub ?: ($term ? $term->slug : ''));
      }
    }

    // 5) Enforce portrait when possible
    if ($front && !tmw_is_portrait($front)) { $try = tmw_try_portrait_variant($front); if ($try) $front = $try; }
    if ($back  && !tmw_is_portrait($back))  { $try = tmw_try_portrait_variant($back);  if ($try) $back  = $try; }

    if (!$front) $front = $placeholder;
    if (!$back)  $back  = $front;

    return ['front' => $front, 'back' => $back, 'link' => $link];
  }
}

/* Admin bar button to purge AWE cache */
add_action('admin_bar_menu', function($bar){
  if (!current_user_can('manage_options')) return;
  $bar->add_node([
    'id'    => 'tmw_aw_clear_cache',
    'title' => 'Purge AWEmpire Cache',
    'href'  => wp_nonce_url(admin_url('?tmw_aw_clear_cache=1'), 'tmw_aw_clear_cache'),
  ]);
}, 100);
add_action('admin_init', function(){
  if ( current_user_can('manage_options') && isset($_GET['tmw_aw_clear_cache']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'tmw_aw_clear_cache') ) {
    delete_transient('tmw_aw_feed_v1');
    wp_safe_redirect(remove_query_arg(['tmw_aw_clear_cache','_wpnonce']));
    exit;
  }
});

/* ======================================================================
 * ACF FIELD GROUPS (now target TAXONOMY=models)
 * ====================================================================== */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) return;

  // Content group (bio etc.) — no hero
  acf_add_local_field_group([
    'key'      => 'group_tmw_model_content_only',
    'title'    => 'Model Content',
    'location' => [[['param' => 'taxonomy', 'operator' => '==', 'value' => 'models']]],
    'position' => 'normal',
    'fields'   => [
      ['key'=>'fld_tmw_bio','label'=>'Biography','name'=>'bio','type'=>'wysiwyg','tabs'=>'all'],
      ['key'=>'fld_tmw_lines','label'=>'Read more: visible lines','name'=>'readmore_lines','type'=>'number','default_value'=>20,'min'=>5,'max'=>100,'step'=>1],
      ['key'=>'fld_tmw_live','label'=>'Live link (optional)','name'=>'live_link','type'=>'url','placeholder'=>'https://...'],
      ['key'=>'fld_tmw_feat_sc','label'=>'Featured models shortcode','name'=>'featured_models_shortcode','type'=>'text','default_value'=>'[featured_models count="4" mode="select-or-random" layout="flipbox"]'],
    ],
  ]);

  // Banner group (new key for models)
  acf_add_local_field_group([
    'key'      => 'group_tmw_model_banner_v2',
    'title'    => 'Banner (1200×350)',
    'location' => [[['param' => 'taxonomy', 'operator' => '==', 'value' => 'models']]],
    'position' => 'normal',
    'fields'   => [
      [
        'key' => 'fld_tmw_banner_source', 'label' => 'Banner source', 'name' => 'banner_source', 'type' => 'button_group',
        'choices' => ['feed'=>'From AWE feed','upload'=>'Upload','url'=>'External URL'], 'default_value'=>'feed',
      ],
      [
        'key'=>'fld_tmw_banner_height','label'=>'Banner height','name'=>'banner_height','type'=>'button_group',
        'choices'=> ['350'=>'1200×350'], 'default_value'=>'350',
      ],
      [
        'key'=>'fld_tmw_banner_url','label'=>'External banner URL','name'=>'banner_image_url','type'=>'url',
        'placeholder'=>'https://example.com/banner.jpg',
        'conditional_logic'=>[[['field'=>'fld_tmw_banner_source','operator'=>'==','value'=>'url']]],
      ],
      [
        'key'=>'fld_tmw_banner_upload','label'=>'Upload banner','name'=>'banner_image','type'=>'image',
        'return_format'=>'array','preview_size'=>'large',
        'conditional_logic'=>[[['field'=>'fld_tmw_banner_source','operator'=>'==','value'=>'upload']]],
      ],
      ['key'=>'fld_tmw_banner_x','label'=>'Position X','name'=>'banner_offset_x','type'=>'range','default_value'=>0,'min'=>-100,'max'=>100,'step'=>1,'append'=>'%'],
      ['key'=>'fld_tmw_banner_y','label'=>'Position Y','name'=>'banner_offset_y','type'=>'range','default_value'=>0,'min'=>-350,'max'=>350,'step'=>1,'append'=>'px'],
    ],
  ]);
});

/* Hide default description & any field literally named "Short Bio" on models */
add_action('admin_head-term.php', function () {
  $screen = get_current_screen();
  if (!$screen || $screen->taxonomy !== 'models') return;
  echo '<style>.term-description-wrap, .form-field.term-description-wrap { display:none !important; }</style>';
});
add_action('admin_footer', function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->base !== 'term' || $screen->taxonomy !== 'models') return;
  ?>
  <script>
  jQuery(function($){
    $('.acf-label label').each(function(){
      var t = $(this).text().trim().toLowerCase();
      if(t === 'short bio'){ $(this).closest('.acf-field').hide(); }
    });
  });
  </script>
  <?php
});

/* 3) Admin: two-column layout (right sidebar) */
add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook !== 'term.php') return;
  $screen = get_current_screen();
  if (!$screen || $screen->taxonomy !== 'models') return;

  wp_enqueue_style(
    'retrotube-child-style',
    get_stylesheet_uri(),
    [],
    tmw_child_style_version()
  );

  $style_path = get_stylesheet_directory() . '/admin/css/admin-banners.css';
  $version    = file_exists($style_path) ? filemtime($style_path) : null;
  wp_enqueue_style(
    'tmw-admin-banner-style',
    get_stylesheet_directory_uri() . '/admin/css/admin-banners.css',
    [],
    $version
  );

  add_action('admin_head', function () {
    echo '<style>
      .tmw-term-two-col{display:flex; gap:24px; align-items:flex-start}
      .tmw-term-two-col .tmw-term-right{width:330px; flex:0 0 330px}
      .tmw-term-two-col .tmw-term-main{flex:1 1 auto; min-width:0}
      @media(max-width:1024px){ .tmw-term-two-col{display:block} .tmw-term-right{width:auto} }
      /* Full-width banner preview */
      #tmw-banner-preview{margin:12px 0 0;}
      #tmw-banner-note{margin:6px 0 0; color:#666}
    </style>';
  });

  add_action('admin_footer', function () {
    ?>
    <script>
    jQuery(function($){
      // Build columns (form left, SEO box right)
      var $wrap = $('#wpbody-content .wrap').first();
      var $form = $wrap.find('form#edittag, form#addtag').first();
      if(!$form.length) return;

      var $main = $('<div class="tmw-term-main"></div>');
      var $right = $('<div class="tmw-term-right"></div>');
      var $cols = $('<div class="tmw-term-two-col"></div>').append($main).append($right);

      $form.appendTo($main);
      $wrap.append($cols);

      var $rank = $('#rank_math_metabox, .rank-math-metabox, #wpseo_meta');
      if ($rank.length){ $rank.appendTo($right).show(); }

      // Insert banner preview UNDER the whole form for full width
      if (!$('#tmw-banner-preview').length){
        $main.append(
          '<div id="tmw-banner-preview" class="tmw-banner-preview">'+
            '<div class="tmw-banner-container">'+
              '<div class="tmw-banner-frame backend" data-banner-height="350">'+
                '<img class="tmw-banner-preview-img" src="data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=" alt="Banner preview" style="display:none;" />'+
              '</div>'+
            '</div>'+
            '<div class="ph">Banner preview (1039×350)</div>'+
          '</div>'+
          '<div id="tmw-banner-note">Tip: drag the sliders to fine-tune the banner offset. Preview matches the front-end.</div>'
        );
      }

      var $preview = $('#tmw-banner-preview');
      var $frame = $preview.find('.tmw-banner-frame');
      var $ph = $preview.find('.ph');
      if (typeof window.tmwSetBannerOffsetY !== 'function') {
        window.tmwSetBannerOffsetY = function(pxValue) {
          var v = Number(pxValue) || 0;
          var root = document.documentElement;
          if (root && root.style) {
            root.style.setProperty('--offset-y', v + 'px');
          }
          var defaultWrap = document.getElementById('tmw-banner-preview') || document.getElementById('tmwBannerPreview');
          var defaultFrame = null;
          if (defaultWrap) {
            if (defaultWrap.classList && defaultWrap.classList.contains('tmw-banner-frame')) {
              defaultFrame = defaultWrap;
            } else {
              defaultFrame = defaultWrap.querySelector('.tmw-banner-frame');
            }
          }
          if (defaultFrame && defaultFrame.style) {
            defaultFrame.style.setProperty('--offset-y', v + 'px');
          }
          return v;
        };
      }
      window.tmwUpdateBannerOffset = window.tmwUpdateBannerOffset || function(preview, value, posX){
        var numeric = parseInt(value, 10);
        if (isNaN(numeric)) numeric = 0;
        var clamped = Math.max(-1000, Math.min(1000, numeric));
        var applied = (typeof window.tmwSetBannerOffsetY === 'function') ? window.tmwSetBannerOffsetY(clamped) : clamped;
        var defaultWrap = document.getElementById('tmw-banner-preview') || document.getElementById('tmwBannerPreview');
        var defaultFrame = null;
        if (defaultWrap) {
          if (defaultWrap.classList && defaultWrap.classList.contains('tmw-banner-frame')) {
            defaultFrame = defaultWrap;
          } else {
            defaultFrame = defaultWrap.querySelector('.tmw-banner-frame');
          }
        }
        var target = preview;
        if (target && target.classList && !target.classList.contains('tmw-banner-frame')) {
          target = target.querySelector ? target.querySelector('.tmw-banner-frame') : null;
        }
        if (!target) {
          target = defaultFrame;
        }
        if (target && target.style && target !== defaultFrame) {
          target.style.setProperty('--offset-y', applied + 'px');
        }
        if (target && target.style) {
          if (typeof posX === 'number' && !isNaN(posX)) {
            target.style.setProperty('--offset-x', posX + '%');
          } else if (!target.style.getPropertyValue('--offset-x')) {
            target.style.setProperty('--offset-x', '50%');
          }
        }
        return clamped;
      };
      // ===== TMW: Dock Height / X / Y controls directly above the preview =====
(function(){
  // small toolbar styling
  if(!document.getElementById('tmw-controls-dock-css')){
    $('head').append(
      '<style id="tmw-controls-dock-css">\
        #tmw-controls-dock{background:#fff;border:1px solid #e5e5e5;border-radius:6px;margin:10px 0 8px;padding:10px 12px;box-shadow:0 1px 2px rgba(0,0,0,.04)}\
        #tmw-controls-dock .acf-field{margin:6px 0;padding:0;border:0}\
        #tmw-controls-dock .acf-label{min-width:160px}\
        #tmw-controls-dock .acf-input input[type=range]{width:220px}\
        #tmw-controls-dock .acf-input input[type=number]{max-width:90px}\
        @media (max-width: 782px){#tmw-controls-dock .acf-label{min-width:auto}}\
      </style>'
    );
  }

  // helper to find an ACF field by key/name (you already use this selector pattern)
  function fieldSel(key,name){
    var $f = $('.acf-field[data-key="'+key+'"]');
    if(!$f.length && name) $f = $('.acf-field[data-name="'+name+'"]');
    return $f;
  }

  // create the dock and move fields into it (keeps original inputs, so saving still works)
  if(!$('#tmw-controls-dock').length){
    var $dock = $('<div id="tmw-controls-dock" class="tmw-controls-dock"></div>').insertBefore($preview);

    // Detach the existing ACF fields
    var $h = fieldSel('fld_tmw_banner_height','banner_height').detach();  // Height (radio group)
    var $x = fieldSel('fld_tmw_banner_x','banner_offset_x').detach();     // Position X (range)
    var $y = fieldSel('fld_tmw_banner_y','banner_offset_y').detach();     // Position Y (range)

    // Order: Height, X, Y
    if($h.length) $dock.append($h);
    if($x.length) $dock.append($x);
    if($y.length) $dock.append($y);

    // Optional: if you also want the Source switch here, uncomment next line:
    // fieldSel('fld_tmw_banner_source','banner_source').detach().prependTo($dock);
  }
})();


      function fieldSel(key,name){
        var $f = $('.acf-field[data-key="'+key+'"]');
        if(!$f.length && name) $f = $('.acf-field[data-name="'+name+'"]');
        return $f;
      }
      function readSrc(){
        var $s = fieldSel('fld_tmw_banner_source','banner_source');
        var v = $s.find('input[type="radio"]:checked').val();
        if(!v) v = $s.find('input[type="hidden"]').val();
        return (v||'feed').trim();
      }
      function readHeight(){
        return 350;
      }
      function readURL(){
        var $u = fieldSel('fld_tmw_banner_url','banner_image_url');
        return ($u.find('input[type="url"], input[type="text"]').val() || '').trim();
      }
      function readUpload(){
        var $up = fieldSel('fld_tmw_banner_upload','banner_image');
        var src = $up.find('.image-wrap img').attr('src');
        return (src || '').trim();
      }
      function readX(){ var $x=fieldSel('fld_tmw_banner_x','banner_offset_x'); return parseFloat($x.find('input').val()||0); }
      function readY(){ var $y=fieldSel('fld_tmw_banner_y','banner_offset_y'); return parseFloat($y.find('input').val()||0); }

      function apply(url){
        var x = readX(), y = readY(), h = readHeight();
        var posX = Math.max(0, Math.min(100, 50 + x));
        var yPx = Math.max(-1000, Math.min(1000, y));
      $frame = $('#tmw-banner-preview .tmw-banner-frame');
      $frame.attr('data-banner-height', h);
      var $img = $frame.find('img.tmw-banner-preview-img');
      $('#tmw-banner-preview .ph').text('Banner preview (1039×'+h+')');
      if(url){
        var safeUrl = String(url).replace(/"/g, '\\"');
        if ($img.length){
          $img.attr('src', safeUrl).attr('aria-hidden', 'false').show();
        }
        if (typeof window.tmwUpdateBannerOffset === 'function' && $frame.length){
          window.tmwUpdateBannerOffset($frame[0], yPx, posX);
        }
        $ph.hide();
      }else{
        if ($img.length){
          $img.removeAttr('src').attr('aria-hidden', 'true').hide();
        }
        if (typeof window.tmwUpdateBannerOffset === 'function' && $frame.length){
          window.tmwUpdateBannerOffset($frame[0], 0);
        }
        $ph.show();
      }
      }

      function refresh(){
        var s = readSrc();
        if(s === 'upload'){ apply(readUpload()); }
        else if(s === 'url'){ apply(readURL()); }
        else {
          // from feed
          $.get(ajaxurl, {action:'tmw_preview_banner', term_id: ($('#tag_ID').val()||0)})
           .done(function(r){ apply(r && r.success && r.data ? r.data.url : ''); })
           .fail(function(){ apply(''); });
        }
      }

      // Initial draw
      refresh();

      // Live updates
      $(document).on('input change keyup blur',
        '.acf-field[data-key="fld_tmw_banner_source"] input,'+
        '.acf-field[data-key="fld_tmw_banner_height"] input,'+
        '.acf-field[data-key="fld_tmw_banner_url"] input,'+
        '.acf-field[data-key="fld_tmw_banner_upload"] input,'+
        '.acf-field[data-key="fld_tmw_banner_x"] input,'+
        '.acf-field[data-key="fld_tmw_banner_y"] input', refresh);

      // After picking image
      $(document).on('click', '.acf-field[data-key="fld_tmw_banner_upload"] .acf-button, .media-modal .media-button-select', function(){
        setTimeout(refresh, 400);
      });
    });
    </script>
    <?php
  });
}, 11);

/* 4) Banner helpers */
if (!function_exists('tmw_pick_banner_from_feed_row')) {
  function tmw_pick_banner_from_feed_row($row) {
    if (!is_array($row)) return '';
    $urls = [];
    $walk = function($v) use (&$walk,&$urls){
      if (is_string($v) && preg_match('~https?://[^\s"]+\.(?:jpe?g|png|webp)(?:\?[^\s"]*)?$~i',$v)) $urls[]=$v;
      elseif (is_array($v)) foreach($v as $vv) $walk($vv);
    };
    $walk($row);
    $urls = array_values(array_unique($urls));
    if (!$urls) return '';
    $score = function($u){
      $s = 0;
      if (strpos($u,'800x600')!==false || strpos($u,'896x504')!==false) $s += 6;   // landscape
      if (strpos($u,'600x800')!==false || strpos($u,'504x896')!==false) $s -= 4;   // portrait
      if (preg_match('~(\d{3,4})x(\d{3,4})~',$u,$m)) $s += max((int)$m[1],(int)$m[2])/1200;
      return $s;
    };
    usort($urls, function($a,$b) use($score){ return $score($b) <=> $score($a); });
    return $urls[0];
  }
}
add_action('wp_ajax_tmw_preview_banner', function(){
  if (!current_user_can('manage_categories')) wp_send_json_error('forbidden', 403);
  $term_id = (int)($_GET['term_id'] ?? 0);
  if (!$term_id) wp_send_json_error('no term', 400);
  $url = tmw_resolve_model_banner_url(0, $term_id);
  wp_send_json_success(['url'=>$url]);
});

/* ======================================================================
 * MODEL PAGE – VIRTUAL TEMPLATE (NO HERO)
 * ====================================================================== */
add_action('widgets_init', function () {
  register_sidebar([
    'name'          => __('Model Sidebar', 'retrotube-child'),
    'id'            => 'model-sidebar',
    'description'   => __('Widgets on single model pages', 'retrotube-child'),
    'before_widget' => '<section class="widget %2$s">',
    'after_widget'  => '</section>',
    'before_title'  => '<h2 class="widget-title">',
    'after_title'   => '</h2>',
  ]);
});

add_action('template_redirect', function(){
  if (!is_tax('models')) return;

  $term    = get_queried_object();
  $term_id = isset($term->term_id) ? (int)$term->term_id : 0;
  $acf_id  = 'models_' . $term_id;

  // Data
  $bio         = function_exists('get_field') ? (get_field('bio', $acf_id) ?: '') : '';
  $read_lines  = function_exists('get_field') ? (int) (get_field('readmore_lines', $acf_id) ?: 20) : 20;
  $featured_sc = function_exists('get_field') ? (get_field('featured_models_shortcode', $acf_id) ?: '[tmw_featured_models count="4"]') : '[tmw_featured_models count="4"]';

  $banner_src  = function_exists('tmw_resolve_model_banner_url') ? tmw_resolve_model_banner_url(0, $term_id) : '';
  $bx          = function_exists('get_field') ? (float)(get_field('banner_offset_x', $acf_id) ?: 0) : 0;
  $by          = function_exists('get_field') ? (float)(get_field('banner_offset_y', $acf_id) ?: 0) : 0;
  $banner_h    = 350;

  $pos_x = max(0, min(100, 50 + $bx));
  $offset_y = (int) round($by);
  if ($offset_y < -1000) {
    $offset_y = -1000;
  }
  if ($offset_y > 1000) {
    $offset_y = 1000;
  }
  get_header();
  ?>
  <div class="tmw-model-page">
    <style>
      .page-header, .entry-header, .single__header, .post__header, .rt-top-banner, .hero, .tmw-model-hero { display:none !important; }

      .tmw-model-grid{display:grid;grid-template-columns:1fr;gap:24px}
      @media(min-width: 992px){ .tmw-model-grid{grid-template-columns:2fr 1fr} }
      .tmw-model-main{min-width:0}

      .tmw-model-banner{
        margin:10px 0 20px;
        border-radius:12px;
      }
      .tmw-model-banner .tmw-model-banner-frame{
        border-radius:12px;
      }
      .tmw-model-title{margin:10px 0 12px}
      .tmw-bio.js-clamp{display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;-webkit-line-clamp:<?php echo (int)$read_lines; ?>}
      .tmw-bio-toggle{margin-top:.5rem}
      .tmw-featured-flipboxes{margin-top:24px}
    </style>

    <div class="container tmw-model-grid">
      <main class="tmw-model-main">
        <?php if ($banner_src): ?>
          <?php
          $banner_style = function_exists('tmw_get_banner_style') ? tmw_get_banner_style($offset_y, $banner_h, [
            'term_id'        => $term_id,
            'image_url'      => $banner_src,
            'render_context' => 'frontend',
          ]) : '';
          ?>
          <div class="tmw-model-banner">
            <div class="tmw-model-banner-frame tmw-banner-frame" style="<?php echo esc_attr($banner_style); ?>">
              <img src="<?php echo esc_url($banner_src); ?>" alt="<?php echo esc_attr($term->name); ?>" />
            </div>
          </div>
        <?php endif; ?>

        <h1 class="tmw-model-title"><?php echo esc_html($term->name); ?></h1>

        <div class="tmw-bio-wrap">
          <div id="tmw-bio" class="tmw-bio js-clamp">
            <?php
              if ($bio) echo wpautop($bio);
              else echo '<p>'.esc_html__('No biography provided yet.','retrotube-child').'</p>';
            ?>
          </div>
          <?php if ($bio): ?>
            <p class="tmw-bio-toggle">
              <a class="morelink" href="#" aria-controls="tmw-bio" aria-expanded="false">
                <?php esc_html_e('Read more','retrotube-child'); ?> <i class="fa fa-chevron-down"></i>
              </a>
            </p>
          <?php endif; ?>
        </div>

        <section class="tmw-featured-flipboxes">
          <?php echo do_shortcode($featured_sc); ?>
        </section>
      </main>

      <aside class="tmw-model-sidebar">
        <?php
          if (is_active_sidebar('model-sidebar')) { dynamic_sidebar('model-sidebar'); }
          else { get_sidebar(); }
        ?>
      </aside>
    </div>
  </div>

  <script>
  (function(){
    var bio = document.getElementById('tmw-bio');
    var wrap = document.querySelector('.tmw-bio-toggle');
    if (!bio || !wrap) return;

    var clone = bio.cloneNode(true);
    clone.style.visibility='hidden'; clone.style.position='absolute';
    clone.style.webkitLineClamp='unset'; clone.classList.remove('js-clamp');
    document.body.appendChild(clone);
    var needs = clone.scrollHeight > bio.clientHeight + 5;
    document.body.removeChild(clone);
    if (!needs) { wrap.style.display='none'; return; }

    var link = wrap.querySelector('a.morelink');
    link.addEventListener('click', function(e){
      e.preventDefault();
      var expanded = link.getAttribute('aria-expanded') === 'true';
      if (expanded) {
        bio.classList.add('js-clamp');
        link.setAttribute('aria-expanded','false');
        link.innerHTML = 'Read more <i class="fa fa-chevron-down"></i>';
      } else {
        bio.classList.remove('js-clamp');
        link.setAttribute('aria-expanded','true');
        link.innerHTML = 'Close <i class="fa fa-chevron-up"></i>';
      }
    });
  })();
  </script>
  <?php
  get_footer();
  exit;
});

/* ======================================================================
 * FEATURED / FLIPBOX SHORTCODES (taxonomy=models)
 * ====================================================================== */

/* Safe image picker (AW → ACF → term thumb → placeholder) + portrait fix */
if (!function_exists('tmw_pick_images_for_term')) {
  function tmw_pick_images_for_term(int $term_id): array {
    $front = ''; $back = '';

    if (function_exists('tmw_aw_card_data')) {
      $cd = tmw_aw_card_data($term_id);
      $front = isset($cd['front']) ? (string)$cd['front'] : '';
      $back  = isset($cd['back'])  ? (string)$cd['back']  : '';
    }

    // ACF overrides (taxonomy: models)
    if ((!$front || !$back) && function_exists('get_field')) {
      $acf_id    = 'models_' . $term_id;
      $acf_front = get_field('actor_card_front', $acf_id); // keep names
      $acf_back  = get_field('actor_card_back',  $acf_id);
      if (!$front && is_array($acf_front) && !empty($acf_front['url'])) $front = esc_url_raw($acf_front['url']);
      if (!$back  && is_array($acf_back)  && !empty($acf_back['url']))  $back  = esc_url_raw($acf_back['url']);
    }

    if ((!$front || !$back)) {
      $thumb_id = (int) get_term_meta($term_id, 'thumbnail_id', true);
      if ($thumb_id) {
        $thumb_url = wp_get_attachment_image_url($thumb_id, 'large');
        if (!$front && $thumb_url) $front = $thumb_url;
        if (!$back  && $thumb_url) $back  = $thumb_url;
      }
    }

    if ($front && function_exists('tmw_is_portrait') && !tmw_is_portrait($front) && function_exists('tmw_try_portrait_variant')) {
      $try = tmw_try_portrait_variant($front); if ($try) $front = $try;
    }
    if ($back  && function_exists('tmw_is_portrait') && !tmw_is_portrait($back)  && function_exists('tmw_try_portrait_variant')) {
      $try = tmw_try_portrait_variant($back);  if ($try) $back  = $try;
    }

    if (!$front) $front = function_exists('tmw_placeholder_image_url') ? tmw_placeholder_image_url() : '';
    if (!$back)  $back  = $front;

    return [$front, $back];
  }
}

if (!function_exists('tmw_get_model_post_for_term')) {
  function tmw_get_model_post_for_term($term) {
    if (is_numeric($term)) {
      $term = get_term((int)$term, 'models');
    }
    if (!$term || is_wp_error($term)) return null;

    $stored_id = (int) get_term_meta($term->term_id, 'tmw_model_post_id', true);
    if ($stored_id) {
      $stored_post = get_post($stored_id);
      if ($stored_post && $stored_post->post_type === 'model' && $stored_post->post_status !== 'trash') {
        return $stored_post;
      }
    }

    if (!post_type_exists('model')) {
      return null;
    }

    $post = get_page_by_path($term->slug, OBJECT, 'model');
    if (!$post) {
      $post = get_page_by_title($term->name, OBJECT, 'model');
    }

    if (!$post || is_wp_error($post)) {
      $legacy = get_page_by_path($term->slug, OBJECT, 'model_bio');
      if (!$legacy) {
        $legacy = get_page_by_title($term->name, OBJECT, 'model_bio');
      }
      if ($legacy && !is_wp_error($legacy)) {
        $converted = wp_update_post([
          'ID'        => $legacy->ID,
          'post_type' => 'model',
        ], true);
        if (!is_wp_error($converted)) {
          $post = get_post($legacy->ID);
        }
      }
    }

    if ($post && !is_wp_error($post) && $post->post_type === 'model' && $post->post_status !== 'trash') {
      update_term_meta($term->term_id, 'tmw_model_post_id', (int) $post->ID);
      return $post;
    }

    if ($stored_id) {
      delete_term_meta($term->term_id, 'tmw_model_post_id');
    }

    return null;
  }
}

if (!function_exists('tmw_get_model_link_for_term')) {
  function tmw_get_model_link_for_term($term): string {
    if (is_numeric($term)) {
      $term = get_term((int)$term, 'models');
    }
    if (!$term || is_wp_error($term)) return '';

    $post = tmw_get_model_post_for_term($term);
    if ($post) {
      $url = get_permalink($post);
      if ($url) return $url;
    }

    $link = get_term_link($term);
    return is_wp_error($link) ? '' : (string) $link;
  }
}

/* Renderer used everywhere for one flipbox card */
if (!function_exists('tmw_render_flipbox_card')) {
  function tmw_render_flipbox_card($term): string {
    if (is_numeric($term)) { $term = get_term((int)$term, 'models'); }
    if (!$term || is_wp_error($term)) return '';

    list($front_url, $back_url) = tmw_pick_images_for_term((int)$term->term_id);

    $ov         = function_exists('tmw_tools_overrides_for_term') ? tmw_tools_overrides_for_term((int)$term->term_id) : ['front_url'=>'','back_url'=>'','css_front'=>'','css_back'=>''];
    $front_url  = ($ov['front_url'] ?: $front_url) ?: (function_exists('tmw_placeholder_image_url') ? tmw_placeholder_image_url() : '');
    $back_url   = ($ov['back_url']  ?: $back_url)  ?: $front_url;

    $front_style = (function_exists('tmw_bg_style') ? tmw_bg_style($front_url) : 'background-image:url('.esc_url($front_url).');') . ($ov['css_front'] ?? '');
    $back_style  = (function_exists('tmw_bg_style') ? tmw_bg_style($back_url)  : 'background-image:url('.esc_url($back_url ).');') . ($ov['css_back']  ?? '');

    $link = tmw_get_model_link_for_term($term);
    $link = apply_filters('tmw_model_flipbox_link', $link, $term);
    $name = $term->name;

    ob_start(); ?>
    <a class="tmw-flip" href="<?php echo esc_url($link); ?>" aria-label="<?php echo esc_attr($name); ?>">
      <div class="tmw-flip-inner">
        <div class="tmw-flip-front" style="<?php echo esc_attr($front_style); ?>">
          <span class="tmw-name"><?php echo esc_html($name); ?></span>
        </div>
        <div class="tmw-flip-back" style="<?php echo esc_attr($back_style); ?>">
          <span class="tmw-view">View profile &raquo;&raquo;&raquo;</span>
        </div>
      </div>
    </a>
    <?php
    return (string)ob_get_clean();
  }
}

/* Featured settings/picker/shortcode */
if (!function_exists('tmw_featured_settings')) {
  function tmw_featured_settings(): array {
    $a = get_option('tmw_mf_featured', []);
    $b = get_option('tmw_mf_settings', []);
    $s = is_array($a) ? $a : [];
    if (is_array($b)) $s = array_merge($s, $b);

    return [
      'source'    => isset($s['random_source']) ? (string)$s['random_source'] : 'all',
      'pool'      => isset($s['curated_pool']) ? (array)$s['curated_pool'] : (isset($s['pool']) ? (array)$s['pool'] : []),
      'show_name' => isset($s['show_names']) ? (bool)$s['show_names'] : true,
      'show_cta'  => isset($s['show_cta'])   ? (bool)$s['show_cta']   : true,
      'cta_text'  => isset($s['cta_text'])   ? (string)$s['cta_text'] : 'View profile »»»',
      'target'    => isset($s['target'])     ? (string)$s['target']    : '_self',
    ];
  }
}
if (!function_exists('tmw_featured_pick_terms')) {
  function tmw_featured_pick_terms(string $mode, int $count, array $settings): array {
    $count = max(1, $count);

    if ($mode === 'pool') {
      $ids = array_values(array_unique(array_map('intval', $settings['pool'] ?? [])));
      if ($ids) {
        shuffle($ids);
        $ids = array_slice($ids, 0, $count);
        $terms = get_terms(['taxonomy'=>'models','hide_empty'=>false,'include'=>$ids]);
        return is_wp_error($terms) ? [] : $terms;
      }
      $mode = 'all';
    }

    $all_ids = get_terms(['taxonomy'=>'models','hide_empty'=>false,'fields'=>'ids']);
    if (is_wp_error($all_ids) || !$all_ids) return [];
    shuffle($all_ids);
    $pick = array_slice($all_ids, 0, $count);
    $terms = get_terms(['taxonomy'=>'models','hide_empty'=>false,'include'=>$pick]);
    return is_wp_error($terms) ? [] : $terms;
  }
}
if (!function_exists('tmw_featured_models_shortcode')) {
  function tmw_featured_models_shortcode($atts = []): string {
    if (!wp_style_is('retrotube-child-style', 'enqueued')) {
      wp_enqueue_style(
        'retrotube-child-style',
        get_stylesheet_uri(),
        [],
        tmw_child_style_version()
      );
    }

    if (!wp_style_is('rt-child-flip', 'enqueued')) {
      if (!wp_style_is('rt-child-flip', 'registered')) {
        $flipboxes_path = get_stylesheet_directory() . '/assets/flipboxes.css';
        $flipboxes_ver  = file_exists($flipboxes_path) ? filemtime($flipboxes_path) : tmw_child_style_version();

        wp_register_style(
          'rt-child-flip',
          get_stylesheet_directory_uri() . '/assets/flipboxes.css',
          ['retrotube-child-style'],
          $flipboxes_ver
        );
      }

      wp_enqueue_style('rt-child-flip');
    }

    $s   = tmw_featured_settings();
    $atts = shortcode_atts([
      'count'  => 4,
      'mode'   => $s['source'] ?: 'all',
      'names'  => $s['show_name'] ? '1' : '0',
      'cta'    => $s['show_cta']  ? '1' : '0',
      'text'   => $s['cta_text'],
      'target' => $s['target'],
      'title'  => 'FEATURED MODELS',
    ], (array)$atts, 'featured_models');

    $count = (int)$atts['count'];
    $mode  = strtolower((string)$atts['mode']);
    if ($mode !== 'pool' && $mode !== 'all') $mode = 'all';

    $terms = tmw_featured_pick_terms($mode, $count, $s);
    if (!$terms) return '';

    ob_start();
    echo '<div class="tmwfm-wrap">';
    if (!empty($atts['title'])) {
      echo '<h3 class="tmwfm-heading">'.esc_html($atts['title']).'</h3>';
    }
    echo '<div class="tmwfm-grid">';
    foreach ($terms as $t) {
      echo tmw_render_flipbox_card($t);
    }
    echo '</div></div>';

    $hide_css = '';
    if (!$atts['names'] || $atts['names']==='0') $hide_css .= '.tmwfm-grid .tmw-name{display:none!important;}';
    if (!$atts['cta']   || $atts['cta']==='0')   $hide_css .= '.tmwfm-grid .tmw-view{display:none!important;}';
    if ($hide_css) {
      echo '<style>'.$hide_css.'</style>';
    }

    return ob_get_clean();
  }
}
/* Back-compat shortcode tag */
add_filter('pre_do_shortcode_tag', function($return, $tag, $attr){
  $t = strtolower($tag);
  if ($t === 'featured_models' || $t === 'tmw_featured_models') {
    return tmw_featured_models_shortcode(is_array($attr) ? $attr : []);
  }
  return $return;
}, 8, 3);

/* ======================================================================
 * MODELS GRID – shortcode [models_flipboxes]  (+ alias [actors_flipboxes])
 * ====================================================================== */
function tmw_models_flipboxes_cb($atts){
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
    'img_source'     => 'auto',
  ], $atts);

  $paged = isset($_GET[$a['page_var']]) ? max(1, intval($_GET[$a['page_var']])) : max(1, intval(get_query_var('paged')), intval(get_query_var('page')));
  $per_page   = max(1, (int)$a['per_page']);
  $offset     = ($paged - 1) * $per_page;
  $hide_empty = filter_var($a['hide_empty'], FILTER_VALIDATE_BOOLEAN);

  $terms = get_terms([
    'taxonomy'   => 'models',
    'hide_empty' => $hide_empty,
    'orderby'    => $a['orderby'],
    'order'      => $a['order'],
    'number'     => $per_page,
    'offset'     => $offset,
  ]);
  if (is_wp_error($terms)) return '';

  $total   = (function_exists('tmw_count_terms') ? tmw_count_terms('models', $hide_empty) : 0);
  $total_p = max(1, (int)ceil($total / $per_page));

  ob_start();
  printf('<div class="tmw-grid tmw-cols-%d">', (int)$a['cols']);

  $i = 0;
  foreach ($terms as $term){
    $link = tmw_get_model_link_for_term($term);
    $link = apply_filters('tmw_model_flipbox_link', $link, $term);

    $front_url = '';
    $back_url  = '';

    if (function_exists('tmw_aw_card_data')) {
      $card = tmw_aw_card_data($term->term_id);
      if (!empty($card['front'])) $front_url = $card['front'];
      if (!empty($card['back']))  $back_url  = $card['back'];
    }

    if (($front_url === '' || $back_url === '') && function_exists('get_field')) {
      $acf_front = get_field('actor_card_front', 'models_'.$term->term_id); // keep field names
      $acf_back  = get_field('actor_card_back',  'models_'.$term->term_id);
      if ($front_url === '' && is_array($acf_front) && !empty($acf_front['url'])) $front_url = $acf_front['url'];
      if ($back_url  === '' && is_array($acf_back)  && !empty($acf_back['url']))  $back_url  = $acf_back['url'];
    }

    $ov = function_exists('tmw_tools_overrides_for_term') ? tmw_tools_overrides_for_term($term->term_id) : ['front_url'=>'','back_url'=>'','css_front'=>'','css_back'=>''];
    $front_url = ($ov['front_url'] ?: $front_url) ?: tmw_placeholder_image_url();
    $back_url  = ($ov['back_url']  ?: $back_url)  ?: $front_url;

    if (function_exists('tmw_same_image') && tmw_same_image($back_url, $front_url) && function_exists('tmw_aw_find_by_candidates')) {
      $cands = [];
      $explicit = get_term_meta($term->term_id, 'tmw_aw_nick', true);
      if (!$explicit) $explicit = get_term_meta($term->term_id, 'tm_lj_nick', true);
      if ($explicit) $cands[] = $explicit;
      $cands[] = $term->slug; $cands[] = $term->name;
      $cands[] = str_replace(['-','_',' '], '', $term->slug);
      $cands[] = str_replace(['-','_',' '], '', $term->name);
      $row = tmw_aw_find_by_candidates(array_unique(array_filter($cands)));
      if ($row && function_exists('tmw_aw_pick_images_from_row')) {
        list($_f, $_b) = tmw_aw_pick_images_from_row($row);
        if ($_b && !tmw_same_image($_b, $front_url)) $back_url = $_b;
      }
    }

    $front_style = (function_exists('tmw_bg_style') ? tmw_bg_style($front_url) : 'background-image:url('.esc_url($front_url).');') . ($ov['css_front'] ?? '');
    $back_style  = (function_exists('tmw_bg_style') ? tmw_bg_style($back_url)  : 'background-image:url('.esc_url($back_url ).');') . ($ov['css_back']  ?? '');

    echo '<a class="tmw-flip" href="'.esc_url($link).'" aria-label="'.esc_attr($term->name).'">';
    echo   '<div class="tmw-flip-inner">';
    echo     '<div class="tmw-flip-front" style="'.esc_attr($front_style).'"><span class="tmw-name">'.esc_html($term->name).'</span></div>';
    echo     '<div class="tmw-flip-back"  style="'.esc_attr($back_style) .'"><span class="tmw-view">View profile &raquo;&raquo;&raquo;</span></div>';
    echo   '</div>';
    echo '</a>';

    if (++$i === 8) {
      $banner_html = '';
      if (!empty($a['banner_html'])) {
        $banner_html = $a['banner_html'];
      } elseif (!empty($a['banner_img']) && !empty($a['banner_url'])) {
        $banner_html = '<a class="tmw-banner" href="'.esc_url($a['banner_url']).'" target="_blank" rel="sponsored nofollow noopener"><img src="'.esc_url($a['banner_img']).'" alt="'.esc_attr($a['banner_alt']).'" width="364" height="45"></a>';
      } else {
        $banner_file = get_stylesheet_directory() . '/assets/models-banner.html';
        if (is_readable($banner_file)) $banner_html = file_get_contents($banner_file);
      }
      if ($banner_html) echo '<div class="tmw-banner-wrap">'.$banner_html.'</div>';
    }
  }
  echo '</div>';

  if (filter_var($a['show_pagination'], FILTER_VALIDATE_BOOLEAN) && $total_p > 1){
    $base  = remove_query_arg($a['page_var']);
    $base  = add_query_arg($a['page_var'], '%#%', $base);
    $links = paginate_links([
      'base'      => $base,
      'format'    => '',
      'current'   => $paged,
      'total'     => $total_p,
      'type'      => 'array',
      'prev_text' => '« Prev',
      'next_text' => 'Next »',
    ]);
    if (!empty($links)) {
      echo '<nav class="tmw-pagination" aria-label="Pagination">';
      foreach ($links as $l) echo $l;
      echo '</nav>';
    }
  }

  return ob_get_clean();
}
add_shortcode('models_flipboxes', 'tmw_models_flipboxes_cb');      // new
add_shortcode('actors_flipboxes', 'tmw_models_flipboxes_cb');      // alias / back-compat

/* ======================================================================
 * CONTENT CLEANUPS (remove video players from post content area)
 * ====================================================================== */
if (!function_exists('tmw_strip_video_in_content_active')) {
  function tmw_strip_video_in_content_active() {
    if (is_admin()) return false;
    if (!is_singular()) return false;
    $pt = get_post_type();
    $video_types = ['post','video','videos','wpsc-video','wp-script-video','wpws_video'];
    return in_array($pt, $video_types, true);
  }
}
add_filter('pre_do_shortcode_tag', function($return, $tag){
  if (!tmw_strip_video_in_content_active()) return $return;
  $video_tags = ['video','playlist','audio','embed','wpvideo','wp_playlist','youtube','vimeo','dailymotion','jwplayer','videojs','fvplayer','plyr','wpsc_video','wps_video','wpws_video','flowplayer','jetpack_video'];
  return in_array(strtolower($tag), $video_tags, true) ? '' : $return;
}, 10, 2);
add_filter('embed_oembed_html', function($html){ return tmw_strip_video_in_content_active() ? '' : $html; }, 10);
add_filter('oembed_dataparse',  function($r){    return tmw_strip_video_in_content_active() ? '' : $r;    }, 10);
add_filter('render_block', function($block_content, $block){
  if (!tmw_strip_video_in_content_active()) return $block_content;
  $name = isset($block['blockName']) ? $block['blockName'] : '';
  if ($name === 'core/video' || $name === 'core/embed' || strpos($name, 'core-embed/') === 0) return '';
  return $block_content;
}, 9, 2);
add_filter('the_content', function ($content) {
  if (!tmw_strip_video_in_content_active()) return $content;
  $patterns = [
    '#<iframe\\b[^>]*>.*?</iframe>#is',
    '#<video\\b[^>]*>.*?</video>#is',
    '#<audio\\b[^>]*>.*?</audio>#is',
    '#<object\\b[^>]*>.*?</object>#is',
    '#<embed\\b[^>]*>.*?</embed>#is',
    '#<figure[^>]*class="[^"]*(wp-block-embed|wp-block-video)[^"]*"[^>]*>.*?</figure>#is',
    '#<div[^>]*class="[^"]*(wp-block-embed|video-js|jwplayer|plyr|flowplayer|responsive-embed|embed-container)[^"]*"[^>]*>.*?</div>#is',
  ];
  foreach ($patterns as $rx) $content = preg_replace($rx, '', $content);
  $content = preg_replace('/\[[^\]]*?video[^\]]*\](?:.*?\[\/[^^\]]*?video[^\]]*\])?/is', '', $content);
  $content = preg_replace('/<p>\s*<\/p>/i', '', $content);
  return $content;
}, 99);
add_filter('the_content', function ($content) {
  if (!function_exists('tmw_strip_video_in_content_active') || !tmw_strip_video_in_content_active()) return $content;
  $content = preg_replace('#<div[^>]*class=(["\']).*?\bplayer\b.*?\1[^>]*>[\s\S]*?</div>#i', '', $content);
  $content = preg_replace('#<div[^>]*style=(["\']).*?aspect-ratio.*?\1[^>]*>\s*(?:<!--.*?-->\s*)*</div>#is', '', $content);
  return $content;
}, 98);
add_filter('body_class', function ($classes) {
  if (!function_exists('tmw_strip_video_in_content_active') || !tmw_strip_video_in_content_active()) return $classes;
  $post = get_queried_object();
  if ($post && is_object($post)) {
    if (stripos((string)$post->post_content, 'class="player"') !== false) $classes[] = 'tmw-no-embed';
  }
  return $classes;
}, 11);
/* ======================================================================
 * UTIL
 * ====================================================================== */
if (!function_exists('tmw_count_terms')) {
  function tmw_count_terms($taxonomy, $hide_empty=false){
    if (function_exists('wp_count_terms')) {
      $count = wp_count_terms(['taxonomy'=>$taxonomy,'hide_empty'=>$hide_empty]);
      if (!is_wp_error($count)) return (int)$count;
    }
    $ids = get_terms(['taxonomy'=>$taxonomy, 'fields'=>'ids', 'hide_empty'=>$hide_empty ]);
    return is_wp_error($ids) ? 0 : count($ids);
  }
}

/* ======================================================================
 * MODELS CUSTOM POST TYPE
 * ====================================================================== */
add_action('init', function () {
  $labels = [
    'name'                  => esc_html__('Models', 'retrotube-child'),
    'singular_name'         => esc_html__('Model', 'retrotube-child'),
    'menu_name'             => esc_html__('Models', 'retrotube-child'),
    'name_admin_bar'        => __('Model', 'retrotube-child'),
    'add_new'               => __('Add New', 'retrotube-child'),
    'add_new_item'          => __('Add New Model', 'retrotube-child'),
    'new_item'              => __('New Model', 'retrotube-child'),
    'edit_item'             => __('Edit Model', 'retrotube-child'),
    'view_item'             => __('View Model', 'retrotube-child'),
    'all_items'             => __('All Models', 'retrotube-child'),
    'search_items'          => __('Search Models', 'retrotube-child'),
    'parent_item_colon'     => __('Parent Models:', 'retrotube-child'),
    'not_found'             => __('No models found.', 'retrotube-child'),
    'not_found_in_trash'    => __('No models found in Trash.', 'retrotube-child'),
    'items_list'            => __('Models list', 'retrotube-child'),
    'items_list_navigation' => __('Models list navigation', 'retrotube-child'),
  ];

  $args = [
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'show_in_rest'       => true,
    'has_archive'        => 'models',
    'rewrite'            => ['slug' => 'model', 'with_front' => false],
    'hierarchical'       => false,
    'supports'           => ['title', 'editor', 'thumbnail', 'comments'],
    'taxonomies'         => ['category', 'post_tag'],
    'menu_icon'          => 'dashicons-groups',
    'capability_type'    => 'post',
    'map_meta_cap'       => true,
  ];

  register_post_type('model', $args);
}, 5);

add_filter('rank_math/post_types', function ($post_types) {
  if (!is_array($post_types)) return $post_types;
  if (!in_array('model', $post_types, true)) {
    $post_types[] = 'model';
  }
  return $post_types;
});

/* ======================================================================
 * BREADCRUMBS (Rank Math)
 * ====================================================================== */
add_filter('rank_math/frontend/breadcrumb/items', function ($crumbs) {
  if (!function_exists('rank_math_the_breadcrumbs')) {
    return $crumbs;
  }

  $models_url = home_url('/models/');

  if (is_post_type_archive('model') || is_post_type_archive('model_bio')) {
    $crumbs = [
      ['label' => 'Home', 'url' => home_url('/')],
      ['label' => 'Models', 'url' => $models_url],
    ];
  } elseif (is_singular('model') || is_singular('model_bio')) {
    $crumbs = [
      ['label' => 'Home', 'url' => home_url('/')],
      ['label' => 'Models', 'url' => $models_url],
      ['label' => get_the_title(), 'url' => ''],
    ];
  }

  foreach ($crumbs as $key => $crumb) {
    if (!is_array($crumb) || !isset($crumb['label'])) {
      continue;
    }

    $label = strtolower(trim((string) $crumb['label']));
    if ($label === 'model' || $label === 'model bio') {
      $crumbs[$key]['label'] = 'Models';
      $crumbs[$key]['title'] = 'Models';
      $crumbs[$key]['url']   = $models_url;

      return $crumbs;
    }
  }

  return $crumbs;
});

/* ======================================================================
 * MODELS TAXONOMY (new internal slug) + redirects from old /actor/*
 * - Public URLs: /model/{term}/
 * - Keeps old /actor/* and /actors/* working with 301 to the new URL.
 * - Flushes permalinks once.
 * ====================================================================== */
if (!defined('TMW_TAX_SLUG')) define('TMW_TAX_SLUG', 'models');   // taxonomy key
if (!defined('TMW_URL_SLUG')) define('TMW_URL_SLUG', 'model-tag'); // public URL base (no CPT collision)

add_action('init', function () {
  $labels = [
    'name'                       => 'Models',
    'singular_name'              => 'Model',
    'menu_name'                  => 'Models',
    'all_items'                  => 'All Models',
    'edit_item'                  => 'Edit Model',
    'view_item'                  => 'View Model',
    'update_item'                => 'Update Model',
    'add_new_item'               => 'Add New Model',
    'new_item_name'              => 'New Model Name',
    'parent_item'                => 'Parent Model',
    'search_items'               => 'Search Models',
    'popular_items'              => 'Popular Models',
    'separate_items_with_commas' => 'Separate models with commas',
    'add_or_remove_items'        => 'Add or remove models',
    'choose_from_most_used'      => 'Choose from the most used models',
    'not_found'                  => 'No models found',
    'back_to_items'              => '← Back to Models',
  ];

  register_taxonomy(TMW_TAX_SLUG, ['model'], [
    'labels'            => $labels,
    'public'            => true,
    'show_ui'           => true,
    'show_admin_column' => true,
    'hierarchical'      => false,
    'query_var'         => TMW_TAX_SLUG,
    'rewrite'           => ['slug' => TMW_URL_SLUG, 'with_front' => false, 'hierarchical' => false],
    'show_in_rest'      => true,
    'rest_base'         => TMW_TAX_SLUG,
  ]);
}, 5);

add_action('init', function () {
  add_rewrite_rule('^actor/([^/]+)/?$',  'index.php?' . TMW_TAX_SLUG . '=$matches[1]', 'top');
  add_rewrite_rule('^actors/([^/]+)/?$', 'index.php?' . TMW_TAX_SLUG . '=$matches[1]', 'top');

  if (!get_option('tmw_models_flush_v3')) {
    flush_rewrite_rules(false);
    update_option('tmw_models_flush_v3', 1);
  }
}, 20);

// --- Force all single model pages to use single-model.php ---
add_filter('template_include', function($template) {
  if (is_singular('model')) {
    $child_template = get_stylesheet_directory() . '/single-model.php';
    if (file_exists($child_template)) {
      return $child_template;
    }
  }
  return $template;
}, 999);

add_action('template_redirect', function () {
  if (is_tax(TMW_TAX_SLUG)) {
    $term = get_queried_object();
    if (!is_wp_error($term) && !empty($term->slug)) {
      $maybe = get_page_by_path($term->slug, OBJECT, 'model');
      if ($maybe) {
        $to = get_permalink($maybe);
        error_log('[ModelFix] Redirecting taxonomy term to CPT: ' . $term->slug . ' → ' . $to);
        wp_redirect($to, 301);
        exit;
      }
    }
  }
});

add_action('after_switch_theme', function () {
  flush_rewrite_rules();
  error_log('[ModelFix] Flushed rewrite rules after theme switch.');
});

// 1) Always show a Model/Models line on single video pages.
// Prefers your 'models' taxonomy; falls back to legacy 'actors'.
add_filter('the_content', function ($content) {
  if ( ! is_singular(['post','video']) || ! in_the_loop() || ! is_main_query() ) return $content;

  // If the theme already prints its own block, do nothing.
  if (strpos($content, 'id="video-actors"') !== false ||
      strpos($content, 'id="video-models"') !== false) {
    return $content;
  }

  $post_id = get_the_ID();
  $terms = get_the_terms($post_id, 'models');
  if (empty($terms) || is_wp_error($terms)) {
    $terms = get_the_terms($post_id, 'actors');
  }
  if (empty($terms) || is_wp_error($terms)) return $content;

  $links = [];
  foreach ($terms as $t) {
    $model_link = tmw_get_model_link_for_term($t);
    if (!$model_link) {
      $fallback = get_term_link($t);
      $model_link = is_wp_error($fallback) ? '' : $fallback;
    }
    if ($model_link) {
      $links[] = sprintf('<a href="%s">%s</a>', esc_url($model_link), esc_html($t->name));
    }
  }

  // singular when one model, plural otherwise
  $label = (count($terms) === 1) ? 'Model' : 'Models';

  $block = '<div id="video-models"><i class="fa fa-star"></i> ' .
           $label . ': ' . implode(', ', $links) . '</div>';

  // Place it right under the date/meta if present; otherwise prepend.
  if (preg_match('~(<div[^>]+id="video-date"[^>]*>.*?</div>)~is', $content)) {
    $content = preg_replace('~(<div[^>]+id="video-date"[^>]*>.*?</div>)~is', '$1' . $block, $content, 1);
  } else {
    $content = $block . $content;
  }
  return $content;
}, 45);

// 2) Keep old ‘actors’ and new ‘models’ in sync on save.
add_action('save_post', function ($post_id) {
  if (wp_is_post_revision($post_id)) return;
  $pt = get_post_type($post_id);
  if ($pt !== 'post' && $pt !== 'video') return;

  if (!taxonomy_exists('models') || !is_object_in_taxonomy($pt, 'models')) {
    return;
  }

  $actors = wp_get_post_terms($post_id, 'actors', ['fields' => 'ids']);
  $models = wp_get_post_terms($post_id, 'models', ['fields' => 'ids']);

  // If plugin set actors, mirror to models
  if (!empty($actors) && !is_wp_error($actors)) {
    wp_set_post_terms($post_id, $actors, 'models', false);
  }
  // If you ever tag only in models, mirror to actors too
  if (!empty($models) && !is_wp_error($models)) {
    wp_set_post_terms($post_id, $models, 'actors', false);
  }
}, 20);
/**
 * Retrotube Child (Flipbox Edition)
 * - Flipboxes + AWE helpers + admin tools
 * - Models virtual template with Banner (1200x350)
 * - Admin: right column on Model edit (RankMath box)
 *
 * IMPORTANT: Single PHP block. Do not add another <?php inside this file.
 */

/* ... all your existing code remains unchanged above ... */

/* ======================================================================
 * UTIL
 * ====================================================================== */
if (!function_exists('tmw_count_terms')) {
  function tmw_count_terms($taxonomy, $hide_empty=false){
    if (function_exists('wp_count_terms')) {
      $count = wp_count_terms(['taxonomy'=>$taxonomy,'hide_empty'=>$hide_empty]);
      if (!is_wp_error($count)) return (int)$count;
    }
    $ids = get_terms(['taxonomy'=>$taxonomy, 'fields'=>'ids', 'hide_empty'=>$hide_empty ]);
    return is_wp_error($ids) ? 0 : count($ids);
  }
}

/* ======================================================================
 * MODELS TAXONOMY (new internal slug) + redirects from old /actor/*
 * ====================================================================== */
// ... your taxonomy + sync code is here ...

// Auto-create Model CPT posts for each 'models' taxonomy term
add_action('admin_init', function () {
    if (!post_type_exists('model')) {
        return;
    }

    $terms = get_terms([
        'taxonomy'   => 'models',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return;
    }

    foreach ($terms as $term) {
        $post = tmw_get_model_post_for_term($term);

        if (!$post) {
            $post = get_page_by_path($term->slug, OBJECT, 'model');
            if (!$post) {
                $post = get_page_by_title($term->name, OBJECT, 'model');
            }
        }

        if ($post && !is_wp_error($post) && $post->post_type !== 'model') {
            $updated_id = wp_update_post([
                'ID'        => $post->ID,
                'post_type' => 'model',
            ], true);
            if (!is_wp_error($updated_id)) {
                $post = get_post($post->ID);
            }
        }

        if (!$post || is_wp_error($post) || $post->post_type !== 'model') {
            $post_id = wp_insert_post([
                'post_type'   => 'model',
                'post_title'  => $term->name,
                'post_name'   => $term->slug,
                'post_status' => 'publish',
                'post_content'=> '',
            ], true);

            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }

            $post = get_post($post_id);
        }

        if (!$post || is_wp_error($post) || $post->post_type !== 'model') {
            continue;
        }

        update_term_meta($term->term_id, 'tmw_model_post_id', (int) $post->ID);
        wp_set_object_terms($post->ID, [(int) $term->term_id], 'models', false);
    }
});
// Redirect taxonomy models -> Model CPT if exists
add_action('template_redirect', function () {
    if (!is_tax('models')) {
        return;
    }

    $term = get_queried_object();
    if (!$term || is_wp_error($term)) {
        return;
    }

    $post = tmw_get_model_post_for_term($term);
    if ($post) {
        $url = get_permalink($post);
        if ($url) {
            wp_safe_redirect($url, 301);
            exit;
        }
    }
});

// Redirect legacy /model/ archive → /models/
add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }

    $post_type_query = get_query_var('post_type');
    if (is_array($post_type_query)) {
        $post_type_query = reset($post_type_query);
    }
    if (is_string($post_type_query)) {
        $post_type_query = strtolower($post_type_query);
    } else {
        $post_type_query = '';
    }
    $redirect_needed = false;

    if (is_post_type_archive('model_bio') || (is_post_type_archive() && $post_type_query === 'model_bio')) {
        $redirect_needed = true;
    } elseif (is_post_type_archive('model') || (is_post_type_archive() && $post_type_query === 'model')) {
        global $wp;
        $request_path = isset($wp->request) ? strtolower(ltrim((string) $wp->request, '/')) : '';

        if ($request_path === 'model' || strpos($request_path, 'model/page/') === 0) {
            $redirect_needed = true;
        }
    }

    if (!$redirect_needed) {
        return;
    }

    $archive_url = home_url('/models/');

    $paged = (int) get_query_var('paged');
    $paged = $paged > 1 ? $paged : 0;

    $target = $archive_url;
    if ($paged > 1) {
        $target = trailingslashit($target) . 'page/' . $paged . '/';
    }

    $query_args = [];
    foreach (wp_unslash($_GET) as $key => $value) {
        if ($key === 'paged' || $key === 'post_type') {
            continue;
        }
        $query_args[$key] = $value;
    }
    if (!empty($query_args)) {
        $target = add_query_arg($query_args, $target);
    }

    wp_safe_redirect($target, 301);
    exit;
});

/* ======================================================================
 * FEATURED MODELS SHORTCODE HELPERS
 * ====================================================================== */
if (!function_exists('tmw_clean_featured_shortcode')) {
  function tmw_clean_featured_shortcode($value) {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') {
      return '';
    }
    if (substr($value, 0, 1) !== '[' || substr($value, -1) !== ']') {
      return '';
    }

    return $value;
  }
}

if (!function_exists('tmw_get_default_featured_shortcode')) {
  function tmw_get_default_featured_shortcode() {
    $value = get_theme_mod('tmw_featured_shortcode_default', '[tmw_featured_models]');
    $value = tmw_clean_featured_shortcode($value);
    if ($value === '') {
      $value = '[tmw_featured_models]';
    }

    return $value;
  }
}

if (!function_exists('tmw_customize_sanitize_featured_shortcode')) {
  function tmw_customize_sanitize_featured_shortcode($value) {
    $value = tmw_clean_featured_shortcode($value);

    if ($value === '') {
      return '[tmw_featured_models]';
    }

    return $value;
  }
}

add_action('customize_register', function ($wp_customize) {
  $wp_customize->add_section('tmw_featured_models_block', [
    'title'    => __('Featured Models Block', 'retrotube-child'),
    'priority' => 160,
  ]);

  $wp_customize->add_setting('tmw_featured_shortcode_default', [
    'type'              => 'theme_mod',
    'default'           => '[tmw_featured_models]',
    'sanitize_callback' => 'tmw_customize_sanitize_featured_shortcode',
    'transport'         => 'refresh',
  ]);

  $wp_customize->add_control('tmw_featured_shortcode_default', [
    'section'     => 'tmw_featured_models_block',
    'type'        => 'text',
    'label'       => __('Featured Models shortcode', 'retrotube-child'),
    'description' => __('Enter the shortcode used when no override is provided.', 'retrotube-child'),
  ]);
});

if (!function_exists('tmw_get_featured_shortcode_for_context')) {
  function tmw_get_featured_shortcode_for_context() {
    $shortcode = tmw_get_default_featured_shortcode();

    if (is_singular()) {
      $post_id = get_queried_object_id();
      if ($post_id) {
        $meta = get_post_meta($post_id, 'tmw_featured_shortcode', true);
        $meta = tmw_clean_featured_shortcode($meta);
        if ($meta !== '') {
          $shortcode = $meta;
        } else {
          $legacy = get_post_meta($post_id, 'featured_flipbox_shortcode', true);
          $legacy = tmw_clean_featured_shortcode($legacy);
          if ($legacy !== '') {
            $shortcode = $legacy;
          }
        }
      }
    } elseif (is_category() || is_tag()) {
      $term = get_queried_object();
      if ($term instanceof WP_Term && !is_wp_error($term)) {
        $meta = get_term_meta($term->term_id, 'tmw_featured_shortcode', true);
        $meta = tmw_clean_featured_shortcode($meta);
        if ($meta !== '') {
          $shortcode = $meta;
        }
      }
    } else {
      $request_path = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : '';
      if ($request_path === 'categories' || $request_path === 'tags') {
        $page = get_page_by_path($request_path);
        if ($page instanceof WP_Post) {
          $meta = get_post_meta($page->ID, 'tmw_featured_shortcode', true);
          $meta = tmw_clean_featured_shortcode($meta);
          if ($meta !== '') {
            $shortcode = $meta;
          } else {
            $legacy = get_post_meta($page->ID, 'featured_flipbox_shortcode', true);
            $legacy = tmw_clean_featured_shortcode($legacy);
            if ($legacy !== '') {
              $shortcode = $legacy;
            }
          }
        }
      }
    }

    return $shortcode;
  }
}

if (!function_exists('tmw_should_output_featured_block')) {
  function tmw_should_output_featured_block() {
    if (is_front_page() || is_home()) {
      return false;
    }

    if (is_post_type_archive('model')) {
      return false;
    }

    return true;
  }
}

if (!function_exists('tmw_featured_block_markup')) {
  function tmw_featured_block_markup() {
    if (!tmw_should_output_featured_block()) {
      return '';
    }

    if (!tmw_featured_block_dedup()) {
      return '';
    }

    $shortcode = tmw_get_featured_shortcode_for_context();
    $shortcode = tmw_clean_featured_shortcode($shortcode);
    if ($shortcode === '') {
      return '';
    }

    set_query_var('tmw_featured_shortcode', $shortcode);
    ob_start();
    get_template_part('partials/featured-models-block');
    $markup = ob_get_clean();
    set_query_var('tmw_featured_shortcode', null);

    return is_string($markup) ? trim($markup) : '';
  }
}

if (!function_exists('tmw_featured_block_output_buffer_start')) {
  function tmw_featured_block_output_buffer_start() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
      return;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
      return;
    }

    if (is_feed() || is_embed()) {
      return;
    }

    if (!tmw_should_output_featured_block()) {
      return;
    }

    if (!empty($GLOBALS['tmw_featured_block_buffer_started'])) {
      return;
    }

    $GLOBALS['tmw_featured_block_buffer_started'] = true;
    ob_start();

    add_action('get_footer', 'tmw_featured_block_inject_into_main', 0);
    add_action('shutdown', 'tmw_featured_block_output_buffer_shutdown', 0);
  }
}

add_action('template_redirect', 'tmw_featured_block_output_buffer_start', 0);

if (!function_exists('tmw_featured_block_inject_into_main')) {
  function tmw_featured_block_inject_into_main() {
    if (empty($GLOBALS['tmw_featured_block_buffer_started'])) {
      return;
    }

    $buffer = ob_get_clean();
    $GLOBALS['tmw_featured_block_buffer_started'] = false;
    remove_action('shutdown', 'tmw_featured_block_output_buffer_shutdown', 0);

    if (!is_string($buffer)) {
      echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      return;
    }

    $markup = tmw_featured_block_markup();

    if ($markup !== '') {
      if (strpos($buffer, '</main>') !== false) {
        $buffer = preg_replace('#</main>#', $markup . '</main>', $buffer, 1);
      } else {
        $buffer .= $markup;
      }
    }

    echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  }
}

if (!function_exists('tmw_featured_block_output_buffer_shutdown')) {
  function tmw_featured_block_output_buffer_shutdown() {
    if (empty($GLOBALS['tmw_featured_block_buffer_started'])) {
      return;
    }

    $buffer = ob_get_clean();
    $GLOBALS['tmw_featured_block_buffer_started'] = false;

    if (!is_string($buffer)) {
      echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      return;
    }

    echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  }
}

if (!function_exists('tmw_featured_block_dedup')) {
  function tmw_featured_block_dedup() {
    if (!is_singular()) {
      return true;
    }

    $shortcode = tmw_get_featured_shortcode_for_context();
    $post_id   = get_queried_object_id();
    if (!$post_id) {
      return true;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
      return true;
    }

    return strpos($post->post_content, $shortcode) === false;
  }
}

/* ======================================================================
 * MODEL BANNER POSITION META BOX
 * ====================================================================== */
if (!function_exists('tmw_render_banner_position_box')) {
  function tmw_render_banner_position_box($post) {
    $stored_y = get_post_meta($post->ID, '_banner_position_y', true);
    $raw_y    = is_numeric($stored_y) ? (int) $stored_y : 0;
    $value    = max(-1000, min(1000, $raw_y));
    $banner   = function_exists('tmw_resolve_model_banner_url') ? tmw_resolve_model_banner_url($post->ID) : '';

    wp_nonce_field('tmw_save_banner_position', 'tmw_banner_position_nonce');

    ob_start();
    $rendered = function_exists('tmw_render_model_banner') ? tmw_render_model_banner($post->ID, 'backend') : false;
    $markup   = ob_get_clean();

    echo '<div id="tmw-banner-preview" class="tmw-banner-preview">';
    if ($rendered && $markup) {
      echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    echo '</div>';

    if (!$rendered) {
      echo '<p><em>' . esc_html__('No banner found. Assign a “Models” term with a banner in ACF, or set a featured image.', 'retrotube-child') . '</em></p>';
    }

    echo '<input type="range" min="-350" max="350" step="1" value="' . esc_attr($value) . '" id="tmwBannerSlider" class="tmw-slider" name="banner_position_y">
    <p><small>Vertical offset (px): <span id="tmwBannerValue">' . esc_html($value) . '</span></small></p>';

    if (defined('TMW_BANNER_DEBUG') && TMW_BANNER_DEBUG) {
      echo '<p><code>Resolved URL: ' . esc_html($banner ? $banner : 'EMPTY') . '</code></p>';
    }

    ob_start();
    ?>
    <script>
        (function(){
            const slider = document.getElementById("tmwBannerSlider");
            const previewWrap = document.getElementById("tmw-banner-preview") || document.getElementById("tmwBannerPreview");
            const previewFrame = previewWrap ? (previewWrap.classList && previewWrap.classList.contains('tmw-banner-frame') ? previewWrap : previewWrap.querySelector('.tmw-banner-frame')) : null;
            const val = document.getElementById("tmwBannerValue");
            if (typeof window.tmwSetBannerOffsetY !== "function") {
                window.tmwSetBannerOffsetY = function(pxValue) {
                    var v = Number(pxValue) || 0;
                    var root = document.documentElement;
                    if (root && root.style) {
                        root.style.setProperty("--offset-y", v + "px");
                    }
                    var defaultWrap = document.getElementById("tmw-banner-preview") || document.getElementById("tmwBannerPreview");
                    var defaultFrame = null;
                    if (defaultWrap) {
                        if (defaultWrap.classList && defaultWrap.classList.contains('tmw-banner-frame')) {
                            defaultFrame = defaultWrap;
                        } else {
                            defaultFrame = defaultWrap.querySelector('.tmw-banner-frame');
                        }
                    }
                    if (defaultFrame && defaultFrame.style) {
                        defaultFrame.style.setProperty("--offset-y", v + "px");
                    }
                    return v;
                };
            }
            window.tmwUpdateBannerOffset = window.tmwUpdateBannerOffset || function(previewEl, value, posX){
                var numeric = parseInt(value, 10);
                if (isNaN(numeric)) { numeric = 0; }
                var clamped = Math.max(-1000, Math.min(1000, numeric));
                var applied = (typeof window.tmwSetBannerOffsetY === "function") ? window.tmwSetBannerOffsetY(clamped) : clamped;
                var defaultWrap = document.getElementById("tmw-banner-preview") || document.getElementById("tmwBannerPreview");
                var defaultFrame = null;
                if (defaultWrap) {
                    if (defaultWrap.classList && defaultWrap.classList.contains('tmw-banner-frame')) {
                        defaultFrame = defaultWrap;
                    } else {
                        defaultFrame = defaultWrap.querySelector('.tmw-banner-frame');
                    }
                }
                var target = previewEl;
                if (target && target.classList && !target.classList.contains('tmw-banner-frame')) {
                    target = target.querySelector ? target.querySelector('.tmw-banner-frame') : null;
                }
                if (!target) {
                    target = defaultFrame;
                }
                if (target && target.style && target !== defaultFrame) {
                    target.style.setProperty("--offset-y", applied + "px");
                }
                if (target && target.style) {
                    if (typeof posX === "number" && !isNaN(posX)) {
                        target.style.setProperty("--offset-x", posX + "%");
                    } else if (!target.style.getPropertyValue("--offset-x")) {
                        target.style.setProperty("--offset-x", "50%");
                    }
                }
                return clamped;
            };
            if (slider && previewFrame) {
                slider.addEventListener("input", function(e){
                    var current = parseInt(e.target.value, 10) || 0;
                    var clamped = Math.max(-1000, Math.min(1000, current));
                    if (clamped !== current) {
                        e.target.value = clamped;
                    }
                    if (val) {
                        val.textContent = clamped;
                    }
                    if (typeof window.tmwUpdateBannerOffset === "function") {
                        window.tmwUpdateBannerOffset(previewFrame, clamped);
                    }
                });
                if (typeof window.tmwUpdateBannerOffset === "function") {
                    var initial = parseInt(slider.value, 10) || 0;
                    window.tmwUpdateBannerOffset(previewFrame, initial);
                    if (val) {
                        val.textContent = Math.max(-1000, Math.min(1000, initial));
                    }
                }
            }
        })();
        document.addEventListener('input', function(e){
            if (!e || !e.target) {
                return;
            }
            if (e.target.id === 'banner_offset_y' || e.target.id === 'tmwBannerSlider') {
                var value = parseInt(e.target.value, 10) || 0;
                var frame = document.querySelector('#tmw-banner-preview .tmw-banner-frame, #tmwBannerPreview .tmw-banner-frame');
                if (frame && frame.style) {
                    frame.style.setProperty('--offset-y', value + 'px');
                }
                var previewImg = document.querySelector('#tmw-banner-preview .tmw-banner-frame img, #tmwBannerPreview .tmw-banner-frame img');
                if (previewImg && previewImg.style) {
                    previewImg.style.setProperty('--offset-y', value + 'px');
                }
            }
        });
    </script>
    <?php
    echo ob_get_clean();
  }
}

add_action('admin_enqueue_scripts', function ($hook) {
  if ('post.php' !== $hook) {
    return;
  }

  $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
  if (!$post_id || 'model' !== get_post_type($post_id)) {
    return;
  }

  wp_enqueue_style(
    'retrotube-child-style',
    get_stylesheet_uri(),
    [],
    tmw_child_style_version()
  );

  $style_path = get_stylesheet_directory() . '/admin/css/admin-banners.css';
  $version    = file_exists($style_path) ? filemtime($style_path) : null;

  wp_enqueue_style(
    'tmw-admin-banner-style',
    get_stylesheet_directory_uri() . '/admin/css/admin-banners.css',
    [],
    $version
  );
});

add_action('admin_enqueue_scripts', function($hook){
    global $post;
    if (in_array($hook, ['post.php', 'post-new.php'], true)
        && isset($post)
        && get_post_type($post) === 'model') {
        $path = get_stylesheet_directory() . '/admin/css/tmw-banner-admin.css';
        $version = file_exists($path) ? filemtime($path) : null;
        wp_enqueue_style(
            'tmw-banner-admin-align',
            get_stylesheet_directory_uri() . '/admin/css/tmw-banner-admin.css',
            [],
            $version
        );
    }
});

add_action('add_meta_boxes', function () {
  add_meta_box('model_banner_position', __('Banner Position (Vertical)', 'retrotube-child'), 'tmw_render_banner_position_box', 'model', 'normal', 'default');
});

add_action('save_post_model', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  if (!isset($_POST['tmw_banner_position_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tmw_banner_position_nonce'])), 'tmw_save_banner_position')) {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  if (isset($_POST['banner_position_y'])) {
    $value = wp_unslash($_POST['banner_position_y']);
    $value = is_numeric($value) ? (int)$value : 0;
    if ($value < -1000) {
      $value = -1000;
    }
    if ($value > 1000) {
      $value = 1000;
    }
    update_post_meta($post_id, '_banner_position_y', $value);
  }
});

/* ======================================================================
 * FEATURED MODELS SHORTCODE META BOX
 * ====================================================================== */
if (!function_exists('tmw_featured_shortcode_meta_box_cb')) {
  function tmw_featured_shortcode_meta_box_cb($post) {
    $value = get_post_meta($post->ID, 'tmw_featured_shortcode', true);
    $value = is_string($value) ? $value : '';

    wp_nonce_field('tmw_featured_shortcode_save', 'tmw_featured_shortcode_nonce');
    ?>
    <p>
      <label for="tmw_featured_shortcode_field" class="screen-reader-text"><?php esc_html_e('Featured Models shortcode (optional)', 'retrotube-child'); ?></label>
      <input type="text" name="tmw_featured_shortcode" id="tmw_featured_shortcode_field" value="<?php echo esc_attr($value); ?>" class="widefat" />
    </p>
    <p class="description"><?php esc_html_e('Leave blank to use [tmw_featured_models].', 'retrotube-child'); ?></p>
    <?php
  }
}

add_action('add_meta_boxes', function () {
  $post_types = ['post', 'page', 'model', 'video', 'videos', 'wpsc-video', 'wp-script-video', 'wpws_video'];
  $post_types = array_unique($post_types);
  foreach ($post_types as $post_type) {
    if (!post_type_exists($post_type)) {
      continue;
    }

    add_meta_box(
      'tmw-featured-shortcode',
      __('Featured Models shortcode (optional)', 'retrotube-child'),
      'tmw_featured_shortcode_meta_box_cb',
      $post_type,
      'side',
      'default'
    );
  }
});

add_action('save_post', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  if (!isset($_POST['tmw_featured_shortcode_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tmw_featured_shortcode_nonce'])), 'tmw_featured_shortcode_save')) {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  $value = '';
  if (isset($_POST['tmw_featured_shortcode'])) {
    $value = tmw_clean_featured_shortcode(wp_unslash($_POST['tmw_featured_shortcode']));
  }

  if ($value !== '') {
    update_post_meta($post_id, 'tmw_featured_shortcode', $value);
  } else {
    delete_post_meta($post_id, 'tmw_featured_shortcode');
  }
});

/* ======================================================================
 * FEATURED MODELS SHORTCODE TERM META
 * ====================================================================== */
if (!function_exists('tmw_featured_shortcode_term_add_field')) {
  function tmw_featured_shortcode_term_add_field($taxonomy) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    wp_nonce_field('tmw_featured_shortcode_term_save', 'tmw_featured_shortcode_term_nonce');
    ?>
    <div class="form-field term-featured-shortcode-wrap">
      <label for="tmw_featured_shortcode_term_field"><?php esc_html_e('Featured Models shortcode (optional)', 'retrotube-child'); ?></label>
      <input type="text" name="tmw_featured_shortcode" id="tmw_featured_shortcode_term_field" value="" class="regular-text" />
      <p class="description"><?php esc_html_e('Leave blank to use [tmw_featured_models].', 'retrotube-child'); ?></p>
    </div>
    <?php
  }
}

if (!function_exists('tmw_featured_shortcode_term_edit_field')) {
  function tmw_featured_shortcode_term_edit_field($term) {
    $value = get_term_meta($term->term_id, 'tmw_featured_shortcode', true);
    $value = is_string($value) ? $value : '';

    wp_nonce_field('tmw_featured_shortcode_term_save', 'tmw_featured_shortcode_term_nonce');
    ?>
    <tr class="form-field term-featured-shortcode-wrap">
      <th scope="row"><label for="tmw_featured_shortcode_term_field"><?php esc_html_e('Featured Models shortcode (optional)', 'retrotube-child'); ?></label></th>
      <td>
        <input type="text" name="tmw_featured_shortcode" id="tmw_featured_shortcode_term_field" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Leave blank to use [tmw_featured_models].', 'retrotube-child'); ?></p>
      </td>
    </tr>
    <?php
  }
}

add_action('category_add_form_fields', 'tmw_featured_shortcode_term_add_field');
add_action('post_tag_add_form_fields', 'tmw_featured_shortcode_term_add_field');
add_action('category_edit_form_fields', 'tmw_featured_shortcode_term_edit_field');
add_action('post_tag_edit_form_fields', 'tmw_featured_shortcode_term_edit_field');

if (!function_exists('tmw_save_featured_shortcode_term_meta')) {
  function tmw_save_featured_shortcode_term_meta($term_id) {
    if (!is_admin()) {
      return;
    }

    if (!isset($_POST['tmw_featured_shortcode_term_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tmw_featured_shortcode_term_nonce'])), 'tmw_featured_shortcode_term_save')) {
      return;
    }

    if (!current_user_can('manage_categories')) {
      return;
    }

    $value = '';
    if (isset($_POST['tmw_featured_shortcode'])) {
      $value = tmw_clean_featured_shortcode(wp_unslash($_POST['tmw_featured_shortcode']));
    }

    if ($value !== '') {
      update_term_meta($term_id, 'tmw_featured_shortcode', $value);
    } else {
      delete_term_meta($term_id, 'tmw_featured_shortcode');
    }
  }
}

add_action('created_category', 'tmw_save_featured_shortcode_term_meta');
add_action('edited_category', 'tmw_save_featured_shortcode_term_meta');
add_action('created_post_tag', 'tmw_save_featured_shortcode_term_meta');
add_action('edited_post_tag', 'tmw_save_featured_shortcode_term_meta');
/**
 * Fix: Prevent PHP warnings in canonical.php
 * Avoids "Undefined array key host/scheme" and strtolower(null) notices.
 */
add_filter( 'redirect_canonical', function( $redirect_url, $requested_url ) {
    // If empty or not a string, cancel redirect
    if ( empty( $redirect_url ) || ! is_string( $redirect_url ) ) {
        return false;
    }

    $parts = wp_parse_url( $redirect_url );

    // Cancel redirect if host/scheme missing
    if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
        return false;
    }

    return $redirect_url;
}, 10, 2 );

add_filter( 'widget_display_callback', function( $instance, $widget, $args ) {
  if ( $widget instanceof wpst_WP_Widget_Videos_Block ) {
    ob_start();
    $widget->widget( $args, $instance );
    $output = ob_get_clean();

    $host = preg_quote( wp_parse_url( home_url(), PHP_URL_HOST ), '#' );
    // Only rewrite links that point directly to ?filter= without the /videos/ prefix.
    $pattern = '#(href=["\'])(?:https?://'.$host.')?/\?filter=([^"\']+)#i';
    $output = preg_replace( $pattern, '$1' . home_url( '/videos/?filter=' ) . '$2', $output );

    echo $output;
    return false;
  }

  return $instance;
}, 10, 3 );

/**
 * Sync LiveJasmin performer profiles with Retrotube Model CPT
 * Triggered automatically after each imported video is linked to a model
 */
add_action( 'lvjm_model_profile_attached_video', 'rt_child_sync_model_profile', 10, 2 );

function rt_child_sync_model_profile( $model_post_id, $video_post_id ) {
    if ( ! $model_post_id || ! $video_post_id ) {
        return;
    }

    $model_post = get_post( $model_post_id );
    if ( ! $model_post || $model_post->post_status === 'trash' ) {
        return;
    }

    // Get performer terms attached to this video
    $performers = wp_get_post_terms( $video_post_id, 'models', [ 'fields' => 'names' ] );
    $performer_name = ! empty( $performers ) ? $performers[0] : $model_post->post_title;

    // Update title if needed
    if ( $model_post->post_title !== $performer_name ) {
        wp_update_post([
            'ID'         => $model_post_id,
            'post_title' => $performer_name,
        ]);
    }

    // Ensure featured image or placeholder
    if ( ! has_post_thumbnail( $model_post_id ) ) {
        $placeholder = get_post_meta( $model_post_id, 'lvjm_model_placeholder_image', true );
        if ( $placeholder && filter_var( $placeholder, FILTER_VALIDATE_URL ) ) {
            // Store placeholder as external featured image meta
            update_post_meta( $model_post_id, '_external_thumbnail_url', esc_url( $placeholder ) );
        }
    }

    // Link related videos (avoid duplicates)
    $related = (array) get_post_meta( $model_post_id, 'rt_model_videos', true );
    if ( ! in_array( $video_post_id, $related, true ) ) {
        $related[] = $video_post_id;
        update_post_meta( $model_post_id, 'rt_model_videos', $related );
    }

    error_log('[ModelSync] Synced performer “' . $performer_name . '” (' . $model_post_id . ') with video ' . $video_post_id);
}

add_action('after_switch_theme', function () {
    flush_rewrite_rules();
    error_log('[ModelFix] Flushed rewrite rules after theme activation.');
});

if (!function_exists('tmw_debug_banner_ratio')) {
  function tmw_debug_banner_ratio($post_id) {
    if (defined('WP_DEBUG') && WP_DEBUG && is_single($post_id)) {
      $banner_url = tmw_resolve_model_banner_url($post_id);
      $meta_y = get_post_meta($post_id, '_banner_position_y', true);
      error_log("[TMW_BANNER_RATIO_FIX] Banner: {$banner_url} | OffsetY: {$meta_y}");
    }
  }

  add_action('wp_footer', function () {
    if (is_singular('model')) {
      tmw_debug_banner_ratio(get_the_ID());
    }
  });
}
