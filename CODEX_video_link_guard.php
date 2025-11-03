<?php
/**
 * TMW Video Link Guard (v3.6.2)
 * - Restores correct permalinks for 'video' post type when something forces them to the homepage.
 * - JS guard fixes overlay links (e.g., .cover-link) that wrongly point to "/".
 * - Safe, no layout changes. Runs only on frontend.
 */
if (!defined('ABSPATH')) { exit; }

/**
 * 2.1 — PHP sanity filter:
 * If a 'video' permalink resolves to the homepage, try to rebuild it safely.
 * Guard against recursion.
 */
add_filter('post_type_link', function ($permalink, $post, $leavename) {
    static $in = false;
    if ($in || !is_object($post) || $post->post_type !== 'video') {
        return $permalink;
    }

    // Normalize for comparison
    $home = untrailingslashit(home_url('/'));
    $link = untrailingslashit((string) $permalink);

    if ($link === $home || $link === '' || $link === '/') {
        $in = true;
        // Attempt a fresh permalink; if still bad, leave original.
        $recomputed = get_permalink($post->ID);
        $in = false;

        if ($recomputed && untrailingslashit($recomputed) !== $home) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[TMW-LINK-GUARD] post_type_link fixed ID=%d from home to %s', (int)$post->ID, $recomputed));
            }
            return $recomputed;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[TMW-LINK-GUARD] post_type_link could not fix ID=%d (left as %s)', (int)$post->ID, $permalink));
        }
    }

    return $permalink;
}, 10, 3);

/**
 * 2.2 — Footer JS: fix overlay links that point to "/"
 * Looks inside common card/widget containers, finds a proper inner link, and copies its href.
 */
add_action('wp_footer', function () {
    if (is_admin()) { return; }
    ?>
    <script id="tmw-link-guard" data-ver="3.6.2">
    (function () {
      "use strict";

      function isHome(href) {
        try {
          var url = new URL(href, location.origin);
          return url.origin === location.origin && (url.pathname === "/" || url.pathname === "");
        } catch(e) { return false; }
      }

      function findRealLinkFrom(container) {
        // Prefer obvious inner anchors that look like singles
        var a = container.querySelector(
          'a[href]:not([href="#"]):not([href="/"]):not([href^="mailto:"]):not([href^="tel:"])'
        );
        return a ? a.getAttribute('href') : null;
      }

      function fixOverlayAnchors(root) {
        var fixed = 0;
        // Typical offenders: .cover-link, .overlay-link, .card-link sitting on top
        var overlays = (root || document).querySelectorAll(
          'a.cover-link, a.overlay-link, a.card-link, a.tmw-cover, a.tmw-overlay'
        );
        overlays.forEach(function (ol) {
          var href = ol.getAttribute('href') || '';
          if (!isHome(href)) { return; }

          // Walk up to a likely card wrapper
          var card = ol.closest('article, .video, .card, .rt-card, .tmw-card, li, .widget');
          if (!card) { card = document; }

          var real = findRealLinkFrom(card);
          if (real && !isHome(real)) {
            ol.setAttribute('href', real);
            ol.dataset.tmwFixedBy = 'link-guard';
            fixed++;
          }
        });

        if (fixed && window.console && console.debug) {
          console.debug('[TMW-LINK-GUARD] fixed overlay anchors:', fixed);
        }
        return fixed;
      }

      // Initial pass
      fixOverlayAnchors(document);

      // Mutation observer for lazy/async inserts
      var mo = new MutationObserver(function (muts) {
        var needs = false;
        for (var i=0; i<muts.length; i++) {
          if (muts[i].addedNodes && muts[i].addedNodes.length) { needs = true; break; }
        }
        if (needs) fixOverlayAnchors(document);
      });
      mo.observe(document.documentElement, { childList: true, subtree: true });

      // Expose manual trigger for QA
      window.tmwFixVideoLinks = function () { return fixOverlayAnchors(document); };
    }());
    </script>
    <?php
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TMW-LINK-GUARD] footer script enqueued (v3.6.2).');
    }
}, 100);
