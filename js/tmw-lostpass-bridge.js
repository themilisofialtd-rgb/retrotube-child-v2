(function ($) {
  $(document).on('submit', '#wpst-reset-password, form[action*="lostpassword"]', function (e) {
    e.preventDefault();

    var $form = $(this);
    var $btn  = $form.find('button[type="submit"]');
    var user  = $.trim($form.find('input[name="user_login"], input[name="user_or_email"], input[name="wpst_user_or_email"], input[type="email"]').val());

    $form.find('.tmw-reset-msg').remove();

    $btn.prop('disabled', true).text('Loading...');

    var ajaxUrl = (window.ajaxurl || (window.tmwLostPass && tmwLostPass.ajax_url)) || '/wp-admin/admin-ajax.php';

    $.post(ajaxUrl, {
      action: (window.tmwLostPass && tmwLostPass.action) || 'tmw_lostpass_bp',
      user_login: user,
      tmw_lostpass_bp_nonce: window.tmwLostPass ? tmwLostPass.nonce : ''
    })
      .done(function (resp) {
        $btn.prop('disabled', false).text('Get new password');
        try {
          var payload = resp && resp.data ? resp.data : resp;
          if (resp && (resp.success || resp.loggedin) && payload && payload.message) {
            $form.replaceWith(payload.message);
          } else if (payload && payload.message) {
            $form.prepend('<div class="tmw-reset-msg">' + payload.message + '</div>');
          } else {
            $form.prepend('<div class="tmw-reset-msg"><p class="alert alert-danger">Unexpected response. Please try again.</p></div>');
          }
        } catch (err) {
          $form.prepend('<div class="tmw-reset-msg"><p class="alert alert-danger">Error. Please try again.</p></div>');
        }
      })
      .fail(function () {
        $btn.prop('disabled', false).text('Get new password');
        $form.prepend('<div class="tmw-reset-msg"><p class="alert alert-danger">Network error. Please try again.</p></div>');
      });
  });
})(jQuery);
