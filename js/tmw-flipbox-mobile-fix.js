jQuery(document).ready(function ($) {
  const isMobile = window.matchMedia('(max-width: 768px)').matches;
  if (!isMobile) return;

  console.log('[TMW-FLIPBOX] Anchor strip active');

  // ✅ Step 1: remove any anchor wrapper around flip-box to prevent jump
  $('.flip-box').each(function () {
    const $flip = $(this);
    const $parent = $flip.parent('a[href]');
    if ($parent.length && ($parent.attr('href') === '#' || $parent.attr('href') === '' || $parent.attr('href') === window.location.href)) {
      $flip.unwrap(); // remove the <a> but keep flip-box
      console.log('[TMW-FLIPBOX] Unwrapped anchor from flip-box');
    }
  });

  // ✅ Step 2: handle flip interaction
  $(document).on('click', '.flip-box, .flip-box *', function (e) {
    const $target = $(e.target);
    const $flip = $target.closest('.flip-box');

    // Allow View Profile link to work
    if ($target.closest('.view-profile').length) {
      e.stopPropagation();
      return true;
    }

    // Prevent any default link or scroll
    e.preventDefault();
    e.stopPropagation();

    // Flip only
    $flip.toggleClass('flipped');
    return false;
  });

  // ✅ Step 3: ensure View Profile link doesn't trigger flip
  $('.flip-box .view-profile a').on('click', function (e) {
    e.stopPropagation();
  });
});
