(function ($) {
  var LOSTPASS_TIMEOUT = 10000;

  function getNonceField($form) {
    return $form.find('input[name="tmw_lostpass_bp_nonce"], input[name="_wpnonce"], input[name="nonce"], input[name="_ajax_nonce"]').filter(function () {
      var name = this.getAttribute('name');
      return name && name.toLowerCase().indexOf('nonce') !== -1;
    }).first();
  }

  function startLoading($btn, $spinner) {
    $btn.prop('disabled', true).addClass('loading');
    if ($spinner && $spinner.length) {
      $spinner.addClass('is-active').show();
    }
  }

  function stopLoading($btn, $spinner) {
    $btn.prop('disabled', false).removeClass('loading');
    if ($spinner && $spinner.length) {
      $spinner.removeClass('is-active').hide();
    }
  }

  function resolveMessageEnvelope(payload) {
    if (!payload) {
      return { ok: false, message: '' };
    }

    var data = payload.data || payload;
    var status = typeof data.status === 'string' ? data.status.toLowerCase() : '';
    var ok = payload.ok === true || payload.success === true || status === 'ok';

    return {
      ok: ok,
      message: data.message || data.msg || data.html || ''
    };
  }

  $(document).on('submit', '#wpst-reset-password, form[action*="lostpassword"]', function (e) {
    e.preventDefault();

    var $form = $(this);
    var $btn = $form.find('button[type="submit"]');
    var $spinner = $form.find('.tmw-ajax-spinner, .spinner').first();
    if (!$spinner.length) {
      $spinner = $('<span class="spinner tmw-ajax-spinner" aria-hidden="true"></span>').insertAfter($btn);
    }

    var $status = $form.find('.tmw-ajax-status');
    if (!$status.length) {
      $status = $('<div class="tmw-ajax-status" />').prependTo($form);
    }

    $form.find('.tmw-reset-msg').remove();
    $status.empty();

    var user = $.trim($form.find('input[name="user_login"], input[name="user_or_email"], input[name="wpst_user_or_email"], input[type="email"]').val());
    var nonceField = getNonceField($form);
    var nonceName = nonceField.length ? nonceField.attr('name') : '';
    var nonceValue = nonceField.length ? nonceField.val() : '';

    var ajaxUrl = (window.ajaxurl || (window.tmwLostPass && tmwLostPass.ajax_url)) || '/wp-admin/admin-ajax.php';
    var params = new URLSearchParams();
    params.append('action', 'tmw_lostpass_bp');
    params.append('wpst_user_or_email', user);
    params.append('user_login', user);
    if (nonceName && nonceValue) {
      params.append(nonceName, nonceValue);
    }

    startLoading($btn, $spinner);

    var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timeoutId = null;

    var fetchOptions = {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString()
    };

    if (controller) {
      fetchOptions.signal = controller.signal;
    }

    var fetchPromise = window.fetch ? window.fetch(ajaxUrl, fetchOptions) : Promise.reject(new Error('Fetch API unavailable'));

    var timeoutPromise = new Promise(function (_, reject) {
      timeoutId = setTimeout(function () {
        if (controller && typeof controller.abort === 'function') {
          controller.abort();
        }
        var timeoutError = new Error('Network error—try again.');
        timeoutError.name = 'AbortError';
        reject(timeoutError);
      }, LOSTPASS_TIMEOUT);
    });

    var request = Promise.race([fetchPromise, timeoutPromise]);

    request
      .then(function (response) {
        if (timeoutId) {
          clearTimeout(timeoutId);
        }
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(function (json) {
        stopLoading($btn, $spinner);

        var envelope = resolveMessageEnvelope(json);

        if (envelope.ok) {
          if (envelope.message) {
            $status.html(envelope.message);
          }
        } else {
          var errorMessage = envelope.message || '<p class="alert alert-danger">Unexpected response. Please try again.</p>';
          $status.html(errorMessage);
        }
      })
      .catch(function (error) {
        if (timeoutId) {
          clearTimeout(timeoutId);
        }
        stopLoading($btn, $spinner);

        if (window.console && console.error) {
          console.error('Lost password request failed', error);
        }

        $status.html('<p class="alert alert-danger">Network error—try again.</p>');
      });
  });
})(jQuery);
