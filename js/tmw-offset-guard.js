// Ensure the banner offset never dips below zero by locking --offset-y to 0px.
(function () {
  const GLOBAL_KEY = '__tmwOffsetGuard__';
  const INSTALLED_KEY = '__tmwOffsetGuardInstalled__';
  if (window[INSTALLED_KEY]) {
    return;
  }

  const search = typeof location === 'object' && typeof location.search === 'string' ? location.search : '';
  const hasQueryBypass = search.indexOf('tmw-no-guard') !== -1;
  const docEl = document.documentElement;
  const hasAttrBypass = !!(docEl && docEl.hasAttribute('data-tmw-no-guard'));

  if (hasQueryBypass || hasAttrBypass) {
    window[INSTALLED_KEY] = true;
    const marker = window[GLOBAL_KEY] && typeof window[GLOBAL_KEY] === 'object' ? window[GLOBAL_KEY] : {};
    marker.installed = true;
    marker.bypassed = true;
    window[GLOBAL_KEY] = marker;

    if (typeof window.fixOffset !== 'function') {
      function fixOffset() {}
      Object.defineProperty(fixOffset, 'name', { value: 'fixOffset' });
      fixOffset.__offsetGuardVersion = '1.1.0';
      window.fixOffset = fixOffset;
    }

    return;
  }

  if (window[GLOBAL_KEY] && window[GLOBAL_KEY].installed) {
    window[INSTALLED_KEY] = true;
    return;
  }

  const TARGET_SELECTOR = '.tmw-banner-frame';
  const MIN_PX = 0; // Clamp only when offset < 0
  const state = {
    target: null,
    observer: null,
    rafId: 0,
    deadline: 0,
    isSetting: false,
  };

  function enforceValue(element) {
    if (!element || state.isSetting) {
      return;
    }
    const computedNum = parseFloat(getComputedStyle(element).getPropertyValue('--offset-y')) || 0;
    if (computedNum >= MIN_PX) return;

    state.isSetting = true;
    try {
      element.style.setProperty('--offset-y', MIN_PX + 'px', 'important');
    } finally {
      state.isSetting = false;
    }
  }

  function ensureObserver(element) {
    if (!state.observer) {
      state.observer = new MutationObserver(() => {
        if (!state.target) {
          return;
        }
        const current = parseFloat(getComputedStyle(state.target).getPropertyValue('--offset-y')) || 0;
        if (current < MIN_PX) enforceValue(state.target);
      });
    }

    state.observer.disconnect();
    state.observer.observe(element, { attributes: true, attributeFilter: ['style'] });
  }

  function locateAndGuard() {
    const element = document.querySelector(TARGET_SELECTOR);
    if (!element) {
      return false;
    }

    if (state.target !== element && state.observer) {
      state.observer.disconnect();
      state.observer = null;
    }

    state.target = element;
    enforceValue(element);
    ensureObserver(element);
    return true;
  }

  function tick() {
    if (locateAndGuard()) {
      state.rafId = 0;
      return;
    }

    if (performance.now() >= state.deadline) {
      state.rafId = 0;
      return;
    }

    state.rafId = requestAnimationFrame(tick);
  }

  function beginSearch() {
    if (state.rafId) {
      cancelAnimationFrame(state.rafId);
      state.rafId = 0;
    }
    state.deadline = performance.now() + 5000;
    tick();
  }

  function destroyObserver() {
    if (state.observer) {
      state.observer.disconnect();
      state.observer = null;
    }
    state.target = null;
  }

  function bootstrap() {
    beginSearch();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  } else {
    bootstrap();
  }

  window.addEventListener('load', bootstrap, { once: true });

  function fixOffset() {
    destroyObserver();
    beginSearch();
  }

  Object.defineProperty(fixOffset, 'name', { value: 'fixOffset' });
  window.fixOffset = fixOffset;
  window.fixOffset.__offsetGuardVersion = '1.1.0';

  const marker = window[GLOBAL_KEY] && typeof window[GLOBAL_KEY] === 'object' ? window[GLOBAL_KEY] : {};
  marker.installed = true;
  marker.bypassed = false;
  window[GLOBAL_KEY] = marker;
  window[INSTALLED_KEY] = true;
})();
