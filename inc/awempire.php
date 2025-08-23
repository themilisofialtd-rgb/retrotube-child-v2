<?php
/**
 * AWEmpire integration helpers.
 */

/** Fetch the AWEmpire feed rows with transient caching. */
function tmw_aw_fetch_rows(){
  $url = defined('AWEMPIRE_FEED_URL') ? AWEMPIRE_FEED_URL : '';
  if (!$url) return [];
  $cache_key = 'tmw_aw_rows';
  $rows = get_transient($cache_key);
  if (false === $rows){
    $res = wp_remote_get($url);
    if (is_wp_error($res)) return [];
    $body = wp_remote_retrieve_body($res);
    $json = json_decode($body, true);
    $rows = $json && isset($json['models']) ? $json['models'] : ($json ?: []);
    set_transient($cache_key, $rows, 600); // 10 min cache
  }
  return is_array($rows) ? $rows : [];
}

/** Find a row by nickname (case-insensitive). */
function tmw_aw_find_row($nick){
  $nick = strtolower(trim($nick));
  foreach (tmw_aw_fetch_rows() as $row){
    if (isset($row['nickname']) && strtolower($row['nickname']) === $nick){
      return $row;
    }
  }
  return null;
}

/** Pick front/back images from a feed row. */
function tmw_aw_pick_images_from_row($row){
  $front = $row['image_large'] ?? ($row['image'] ?? '');
  $back  = $row['image_back']  ?? $front;
  return [$front, $back];
}

/** Build outbound link optionally appending subAffId. */
function tmw_aw_build_link($url, $subaff=''){
  if (!$url) return '';
  $sep = strpos($url, '?') !== false ? '&' : '?';
  if ($subaff) $url .= $sep.'subAffId='.rawurlencode($subaff);
  return esc_url($url);
}

// --- Add under the existing helpers in inc/awempire.php ---

/** Build nickname guesses from term slug/name (lowercase, no spaces/underscores). */
function tmw_aw_guess_candidates($term_id) {
  $t = get_term($term_id);
  if (!$t || is_wp_error($t)) return [];
  $slug = strtolower($t->slug);
  $name = strtolower(trim($t->name));
  $name_ns = preg_replace('/\s+/', '', $name);          // remove spaces
  $name_us = preg_replace('/\s+/', '_', $name);         // spaces -> _
  $name_ds = preg_replace('/\s+/', '-', $name);         // spaces -> -
  $uniq = array_values(array_unique(array_filter([$slug, $name, $name_ns, $name_us, $name_ds])));
  return $uniq;
}

/** Try multiple candidates against the feed; returns ['nick'=>..., 'row'=>...] or null. */
function tmw_aw_try_candidates(array $cands) {
  foreach ($cands as $cand) {
    $row = tmw_aw_find_row($cand);
    if ($row) return ['nick'=>$cand, 'row'=>$row];
  }
  return null;
}

function tmw_aw_card_data($term_id){
  $place = get_stylesheet_directory_uri().'/assets/img/placeholders/model-card.jpg';

  // 1) explicit meta (if set by you)
  $nick  = get_term_meta($term_id,'tmw_aw_nick',true);
  $front = get_term_meta($term_id,'tmw_aw_front',true);
  $back  = get_term_meta($term_id,'tmw_aw_back',true);
  $sub   = get_term_meta($term_id,'tmw_aw_subaff',true);

  // 2) if no explicit nickname, auto-guess from slug/name
  $row = null;
  if ($nick) {
    $row = tmw_aw_find_row($nick);
  } else {
    $hit = tmw_aw_try_candidates( tmw_aw_guess_candidates($term_id) );
    if ($hit) { $nick = $hit['nick']; $row = $hit['row']; }
  }

  if ($row){
    if (!$front || !$back){
      list($f,$b) = tmw_aw_pick_images_from_row($row);
      $front = $front ?: $f;
      $back  = $back  ?: $b;
    }
    $link = tmw_aw_build_link(($row['tracking_url'] ?? ($row['url'] ?? '')), $sub ?: $nick);
  } else {
    $link = '';
  }

  return [
    'front' => $front ?: $place,
    'back'  => $back  ?: ($front ?: $place),
    'link'  => $link,
  ];
}
