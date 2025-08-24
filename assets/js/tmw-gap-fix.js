document.addEventListener('DOMContentLoaded', function () {
  var desc = document.querySelector('#video-about .video-description .desc');
  if (!desc) return;

  // Remove the duplicate player if it's the first element
  var node = desc.firstElementChild;
  var isDup = n => n && (
    n.classList?.contains('player') ||
    n.matches?.('.responsive-player,iframe,.wp-block-embed,figure.wp-block-video')
  );

  while (isDup(node)) {
    var next = node.nextElementSibling;
    node.remove();
    node = next;
  }

  // Some embeds drop a <script> right after – clean that too
  if (node && node.tagName === 'SCRIPT' && /tbplyr/i.test(node.src || '')) {
    node.remove();
  }
});

