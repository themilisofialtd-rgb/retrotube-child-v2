/* global jQuery */
(function ($) {
  'use strict';

  // Expect a form inside the modal with a single input for user/email.
  // Adjust selectors if your template differs.
  var $modal = $('#wpst-reset-modal, .wpst-reset-modal, #wpst-reset, #tmw-reset-modal').first();
  var $form  = $modal.find('form').first();
  var $input = $form.find('input[type="text"], input[name="wpst_user_or_email"], input[name="user_login"], input[name="user_email"]').first();
  var $btn   = $form.find('button[type="submit"], .btn[type="submit"]').first();
  var $msg   = $form.find('.tmw-lostpass-msg');

  if ($msg.length === 0) {
    $msg = $('<div class="tmw-lostpass-msg" style="margin-top:10px;"></div>').appendTo($form);
  }

  function setLoading(isLoading) {
    if (isLoading) {
      $btn.prop('disabled', true);
      $btn.data('orig', $btn.text());
      $btn.text('Loading…');
    } else {
      $btn.prop('disabled', false);
      if ($btn.data('orig')) $btn.text($btn.data('orig'));
    }
  }

  function renderMessage(html) {
    $msg.html(html || '');
  }

  $form.on('submit', function (e) {
    e.preventDefault();

    var value = ($input.val() || '').trim();
    if (!value) {
      renderMessage('<p class="alert alert-danger">Please enter your username or email.</p>');
      return;
    }

    var nonce = $form.find('input[name="tmw_lostpass_bp_nonce"]').val() || $form.data('nonce') || '';
    var data = {
      action: 'wpst_lostpassword',          // child handler hook
      tmw_lostpass_bp_nonce: nonce,
      // send all variants so PHP can pick any
      wpst_user_or_email: value,
      user_login: value,
      user_email: value,
      email: value,
      login: value
    };

    setLoading(true);
    renderMessage('');

    $.post(window.ajaxurl || '/wp-admin/admin-ajax.php', data)
      .done(function (res) {
        // Normalize result
        var ok = false, message = '';
        try {
          if (typeof res === 'string') {
            // If server echoed JSON string, try parse
            res = JSON.parse(res);
          }
        } catch (err) { /* ignore */ }

        if (res && typeof res === 'object') {
          ok = !!(res.ok || res.success);
          message = res.message || '';
        }

        if (!message) {
          message = ok
            ? '<p class="alert alert-success">Password Reset. Please check your email.</p>'
            : '<p class="alert alert-danger">Something went wrong. Please try again.</p>';
        }

        renderMessage(message);

        // Optional: you could auto-close the modal after success.
        // if (ok) setTimeout(function(){ $modal.find('.close, .mfp-close').trigger('click'); }, 1200);
      })
      .fail(function () {
        renderMessage('<p class="alert alert-danger">Network error. Please try again.</p>');
      })
      .always(function () {
        setLoading(false);
      });
  });

})(jQuery);
