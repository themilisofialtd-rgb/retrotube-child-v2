<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v3.6.4 — Filter Links (no redirects)
 * - Allow 'filter' as a public query var so SEO/canonical layers don't strip/loop.
 * - Print a tiny JS that rewrites any /?filter=... to the Videos archive URL client-side.
 * - Works for sidebar widgets and Videos page blocks without touching plugin templates.
 */

// 2.1 — Whitelist the query var so WP/SEO understand it.
add_filter('query_vars', function ($vars) {
    if (!in_array('filter', $vars, true)) { $vars[] = 'filter'; }
    return $vars;
});

// 2.2 — (Optional) prevent over-eager canonical on filter requests.
add_filter('redirect_canonical', function ($redirect_url, $requested_url) {
    // We DO NOT redirect anymore. Just let the URL be.
    if (isset($_GET['filter'])) { return false; }
    return $redirect_url;
}, 10, 2);

// 2.3 — Frontend fixer: normalize any bad /?filter=xyz links to the video archive base.
add_action('wp_footer', function () {
    if (is_admin()) return;

    $archive = get_post_type_archive_link('video');
    if (!$archive) { $archive = home_url('/videos/'); } // safety fallback

    ?>
    <script id="tmw-filter-links" data-ver="3.6.4">
    (function () {
      "use strict";

      var VIDEO_ARCHIVE = <?php echo wp_json_encode(trailingslashit($archive)); ?>;

      function sameOrigin(u) {
        try { var x = new URL(u, location.origin); return x.origin === location.origin; }
        catch(e) { return false; }
      }

      function needsFix(href) {
        if (!href) return false;
        // Accept absolute or relative URLs that contain ?filter=...
        if (href.indexOf('filter=') === -1) return false;

        try {
          var url = new URL(href, location.origin);
          // If already under the archive path, don't touch.
          var ap = new URL(VIDEO_ARCHIVE, location.origin).pathname.replace(/\/+$/,'') + '/';
          var up = url.pathname.replace(/\/+$/,'') + '/';
          return (sameOrigin(url.href) && up.indexOf(ap) !== 0);
        } catch(e) {
          return false;
        }
      }

      function fixOne(a) {
        var href = a.getAttribute('href');
        if (!needsFix(href)) return false;

        var url = new URL(href, location.origin);
        var target = new URL(VIDEO_ARCHIVE, location.origin);
        // Preserve the filter value (and any other query params).
        url.searchParams.forEach(function(v,k){ target.searchParams.set(k, v); });
        a.setAttribute('href', target.href);
        a.dataset.tmwFilterFixed = '1';
        return true;
      }

      function scan(root) {
        var n = 0;
        var links = (root || document).querySelectorAll('a[href*="filter="]');
        links.forEach(function(a){ if (fixOne(a)) n++; });
        if (n && console && console.debug) console.debug('[TMW] filter links normalized:', n);
        return n;
      }

      // Initial pass + dynamic inserts (widgets/blocks loaded later)
      scan(document);
      var mo = new MutationObserver(function(muts){
        for (var i=0;i<muts.length;i++){
          if (muts[i].addedNodes && muts[i].addedNodes.length) { scan(document); break; }
        }
      });
      mo.observe(document.documentElement, { childList:true, subtree:true });

      // Expose a manual tester
      window.tmwFixFilterLinks = function(){ return scan(document); };
    }());
    </script>
    <?php
}, 100);
