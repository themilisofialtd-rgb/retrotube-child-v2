(function () {
  if (!window.TMW_LOSTPASS) return;
  // Never interfere on the actual wp-login page itself
  if (TMW_LOSTPASS.is_login) return;

  function qs(name) {
    var m = new RegExp('[?&]' + name + '=([^&#]*)').exec(location.search);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  function openEmbed(url) {
    var modal = document.getElementById('tmw-lostpass-modal');
    var frame = document.getElementById('tmw-lostpass-frame');
    var close = document.getElementById('tmw-lostpass-close');
    if (!modal || !frame) { window.location.href = url; return; }
    frame.src = url;
    modal.style.display = 'block';
    document.documentElement.style.overflow = 'hidden';

    function closeEmbed() {
      modal.style.display = 'none';
      document.documentElement.style.overflow = '';
      try { frame.src = 'about:blank'; } catch (e) {}
    }
    close && close.addEventListener('click', closeEmbed, { once: true });
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeEmbed();
    });
  }

  function bindTriggers() {
    var sels = (TMW_LOSTPASS.selectors || []).join(',');
    if (!sels) return;
    document.addEventListener('click', function (e) {
      var el = e.target.closest(sels);
      if (!el) return;
      // Only hijack links to lost/login; leave other anchors alone
      var href = el.getAttribute('href') || '';
      if (!/wp-login\.php/.test(href) && !/lostpassword|forgot/i.test(href)) return;

      e.preventDefault();
      // If user clicked a generic "login" link, route to lost page (users can switch inside)
      var target = /action=/.test(href) ? href : TMW_LOSTPASS.lost_url;
      // Always ensure cache-busting
      if (target.indexOf('_tmwts=') === -1) {
        target += (target.indexOf('?') === -1 ? '?' : '&') + '_tmwts=' + Date.now();
      }
      // Always pass redirect_to toward the front-end account page
      if (target.indexOf('redirect_to=') === -1) {
        target += '&redirect_to=' + encodeURIComponent(TMW_LOSTPASS.account);
      }
      openEmbed(target);
    }, { capture: true });
  }

  // If we arrive from the email with ?tmw_reset=<encoded rp url>, open it in the modal.
  function maybeOpenFromEmail() {
    var enc = qs('tmw_reset');
    if (!enc) return;
    try {
      var url = decodeURIComponent(enc);
      // Safety: ensure it's our own login endpoint
      if (/\/wp-login\.php/.test(url)) {
        // add front-end redirect in case the page shows a "Log in" link
        if (url.indexOf('redirect_to=') === -1) {
          url += (url.indexOf('?') === -1 ? '?' : '&') + 'redirect_to=' + encodeURIComponent(TMW_LOSTPASS.account);
        }
        openEmbed(url);
        // Clean the URL so refreshes don't reopen the modal
        if (history && history.replaceState) {
          var clean = location.href.replace(/([?&])tmw_reset=[^&#]*&?/, '$1').replace(/[?&]$/, '');
          history.replaceState({}, document.title, clean);
        }
      }
    } catch (e) {}
  }

  // Init
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      bindTriggers();
      maybeOpenFromEmail();
    });
  } else {
    bindTriggers();
    maybeOpenFromEmail();
  }
})();
