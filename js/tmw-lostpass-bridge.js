(function ($, win) {
  function ajaxURL() {
    if (win.tmwLostpass && win.tmwLostpass.ajaxurl) return win.tmwLostpass.ajaxurl;
    return (win.ajaxurl || '/wp-admin/admin-ajax.php'); // last resort
  }

  // Works with the stock RetroTube modal form (#wpst-reset-password-form).
  $(document).on('submit', '#wpst-reset-password-form', function (e) {
    e.preventDefault();

    var $form = $(this);
    var $btn  = $form.find('button[type="submit"]');
    var $in   = $form.find('input[name="user_login"], input[name="username"], input[name="email"]');
    var val   = $.trim($in.val());

    $btn.prop('disabled', true).text('Loading...');

    $.post(ajaxURL(), {
      action: 'wpst_reset_password',
      username: val
    })
    .done(function (resp) {
      var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'If an account exists, you will receive an email.';
      $form.find('.tmw-lostpass-msg, .alert').remove();
      $('<p class="alert alert-success tmw-lostpass-msg" />').text(msg).insertBefore($btn.closest('.form-group, .form-actions').first());
    })
    .fail(function (xhr) {
      var msg = 'Something went wrong. Please try again.';
      try { var r = JSON.parse(xhr.responseText); if (r && r.data && r.data.message) msg = r.data.message; } catch (e) {}
      $form.find('.tmw-lostpass-msg, .alert').remove();
      $('<p class="alert alert-danger tmw-lostpass-msg" />').text(msg).insertBefore($btn.closest('.form-group, .form-actions').first());
    })
    .always(function () {
      $btn.prop('disabled', false).text('Get new password');
    });
  });
})(jQuery, window);

