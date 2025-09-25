<?php
/**
 * Single Model (models taxonomy)
 * Banner + Title Block + Bio (toggle) + Sidebar
 * Theme: Retrotube Child (Flipbox Edition)
 */
get_header();

$term    = get_queried_object();
$term_id = isset($term->term_id) ? (int) $term->term_id : 0;
$acf_id  = 'models_' . $term_id;

/* Biography (from ACF) */
$bio        = function_exists('get_field') ? (get_field('bio', $acf_id) ?: '') : '';
$read_lines = function_exists('get_field') ? ((int)(get_field('readmore_lines', $acf_id) ?: 20)) : 20;

/* Banner image */
$banner_src = function_exists('tmw_resolve_model_banner_url') ? tmw_resolve_model_banner_url($term_id) : '';
$bx   = function_exists('get_field') ? (float)(get_field('banner_offset_x', $acf_id) ?: 0) : 0;
$by   = function_exists('get_field') ? (float)(get_field('banner_offset_y', $acf_id) ?: 0) : 0;
$rot  = function_exists('get_field') ? (float)(get_field('banner_rotate',   $acf_id) ?: 0) : 0;
$pick = function_exists('get_field') ? (string)(get_field('banner_height',  $acf_id) ?: '350') : '350';
$banner_h = ($pick === '300') ? 300 : 350;

$pos_x = max(0, min(100, 50 + $bx));
$pos_y = max(0, min(100, 50 + $by));
?>
<div class="tmw-model-page">
  <div class="tmw-model-grid container" style="display:grid;grid-template-columns:1fr;gap:24px">
    <style>
      @media(min-width: 992px){ .tmw-model-grid{grid-template-columns: 2fr 1fr} }
      .tmw-model-banner{width:100%; height:<?php echo (int)$banner_h; ?>px; overflow:hidden; border-radius:12px; background:#000; margin:0 0 20px}
      .tmw-model-banner img{
        width:100%; height:100%; object-fit:cover; display:block;
        transform-origin:50% 50%;
        backface-visibility:hidden;
        will-change:transform;
      }
      .tmw-bio.js-clamp{display:-webkit-box; -webkit-box-orient:vertical; overflow:hidden; -webkit-line-clamp:<?php echo (int)$read_lines; ?>}
      .tmw-bio-toggle{margin-top:.5rem}
    </style>

    <main class="tmw-model-main">
      <?php if ($banner_src): ?>
      <div class="tmw-model-banner">
        <img
          id="tmw-banner-img-<?php echo (int)$term_id; ?>"
          src="<?php echo esc_url($banner_src); ?>"
          alt="<?php echo esc_attr($term->name); ?>"
          fetchpriority="high" decoding="async"
          data-rot="<?php echo esc_attr($rot); ?>"
          style="object-position: <?php echo $pos_x; ?>% <?php echo $pos_y; ?>%;">
      </div>
      <script>
      (function(){
        var img = document.getElementById('tmw-banner-img-<?php echo (int)$term_id; ?>');
        if(!img) return;

        function coverScale(deg, W, H){
          var rad = Math.abs(deg) * Math.PI / 180;
          var c = Math.abs(Math.cos(rad)), s = Math.abs(Math.sin(rad));
          var r = W / H;
          var scaleW = 1 / (c + (1/r)*s);
          var scaleH = 1 / (r*s + c);
          return Math.max(scaleW, scaleH);
        }
        function apply(){
          var box = img.parentElement;
          var W = box.clientWidth || 1, H = box.clientHeight || 1;
          var rot = parseFloat(img.getAttribute('data-rot') || '0');
          var scale = coverScale(rot, W, H) * 1.02;
          img.style.transform = 'translateZ(0) rotate(' + rot + 'deg) scale(' + scale + ')';
        }
        if (img.complete) apply(); else img.addEventListener('load', apply, {once:true});
        window.addEventListener('resize', apply, {passive:true});
      })();
      </script>
      <?php endif; ?>

      <?php
        // Title block (uses helper if available)
        if (function_exists('tmw_render_model_title_block')) {
          echo tmw_render_model_title_block($term);
        } else {
          echo '<h1 class="entry-title">'.esc_html($term->name).'</h1>';
        }
      ?>

      <div class="tmw-bio-wrap">
        <div id="tmw-bio" class="tmw-bio js-clamp">
          <?php
          if ($bio) {
            echo wpautop($bio);
          } else {
            echo '<p>' . esc_html__('No biography provided yet.', 'retrotube-child') . '</p>';
          }
          ?>
        </div>

        <?php if ($bio): ?>
          <p class="tmw-bio-toggle">
            <a class="morelink" href="#" aria-controls="tmw-bio" aria-expanded="false">
              <?php esc_html_e('Read more', 'retrotube-child'); ?> <i class="fa fa-chevron-down"></i>
            </a>
          </p>
        <?php endif; ?>
      </div>

    </main>

    <aside class="tmw-model-sidebar">
      <?php if (is_active_sidebar('model-sidebar')) {
        dynamic_sidebar('model-sidebar');
      } else {
        get_sidebar();
      } ?>
    </aside>
  </div>
</div>

<script>
(function(){
  var bio = document.getElementById('tmw-bio');
  var toggleWrap = document.querySelector('.tmw-bio-toggle');
  if (!bio || !toggleWrap) return;

  var clone = bio.cloneNode(true);
  clone.style.visibility='hidden'; clone.style.position='absolute';
  clone.style.webkitLineClamp='unset'; clone.classList.remove('js-clamp');
  document.body.appendChild(clone);
  var needsToggle = clone.scrollHeight > bio.clientHeight + 5;
  document.body.removeChild(clone);
  if (!needsToggle) { toggleWrap.style.display='none'; return; }

  var link = toggleWrap.querySelector('a.morelink');
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

<?php get_footer(); ?>
