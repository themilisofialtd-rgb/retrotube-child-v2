(function(){
  const q = (sel) => Array.from(document.querySelectorAll(sel));
  const imgSel = [
    '.single-model .entry-header .tmw-banner-frame > img',
    '.single-model .entry-header .tmw-banner-frame picture > img',
    '.single-model .tmw-banner-frame > img',
    '.single-model .tmw-banner-frame picture > img',
    '.single-model .tmw-model-hero > img',
    '.single-model .tmw-model-hero picture > img',
    '.single-model .entry-header .wp-post-image',
    '.single-model .tmw-banner-frame .wp-post-image'
  ].join(',');

  const bgSel = [
    '.single-model .entry-header .tmw-banner-frame',
    '.single-model .tmw-banner-frame',
    '.single-model .tmw-model-hero',
    '.single-model .tmw-banner-container',
    '.single-model .tmw-banner-frame::before',
    '.single-model .tmw-model-hero::before'
  ].join(',');

  function logComputed() {
    console.group('TMW Mobile Hero Auditor');
    const imgs = q(imgSel);
    const bgs  = q(bgSel);
    imgs.forEach((el, i) => {
      const cs = getComputedStyle(el);
      console.log('[IMG '+i+'] object-position:', cs.objectPosition, 'fit:', cs.objectFit, el);
    });
    bgs.forEach((el, i) => {
      const cs = getComputedStyle(el);
      console.log('[BG '+i+'] background-position:', cs.backgroundPosition, 'Y:', cs.backgroundPositionY, el);
    });
    console.groupEnd();
  }

  function forceInlineIfRequested() {
    if (!window.TMW_MOBILE_HERO_FORCE_INLINE) return;
    const val = '50% calc(var(--tmw-hero-vpos, 50%) + var(--offset-y, 0px))';
    q(imgSel).forEach(el => {
      try { el.style.setProperty('object-position', val, 'important'); } catch(e){}
    });
    q(bgSel).forEach(el => {
      try {
        el.style.setProperty('background-position-x', '50%', 'important');
        el.style.setProperty('background-position-y', 'calc(var(--tmw-hero-vpos, 50%) + var(--offset-y, 0px))', 'important');
      } catch(e){}
    });
  }

  function badge(msg, ok) {
    const b = document.createElement('div');
    b.textContent = msg;
    b.style.cssText = 'position:fixed;left:8px;bottom:8px;z-index:99999;padding:8px 10px;border-radius:6px;font:12px/1.2 system-ui;'
                      +(ok?'background:#0a0;color:#fff;':'background:#a00;color:#fff;');
    document.body.appendChild(b);
    setTimeout(()=>b.remove(), 6000);
  }

  // Run only on small viewports
  const mq = window.matchMedia('(max-width: 840px)');
  if (!mq.matches) { console.info('TMW Auditor: Desktop viewport; nothing to do.'); return; }

  forceInlineIfRequested();
  logComputed();

  // Quick pass/fail badge for humans: if any IMG still shows "50% 50%" it's likely centered
  const anyCentered = q(imgSel).some(el => getComputedStyle(el).objectPosition.trim() === '50% 50%');
  badge(anyCentered ? 'Hero parity: NOT OK (still centered)' : 'Hero parity: OK', !anyCentered);
})();
