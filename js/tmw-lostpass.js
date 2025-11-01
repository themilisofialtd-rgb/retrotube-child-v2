(function($){
  $(document).on('submit', 'form', function(e){
    var $form = $(this);

    // Heuristic: only intercept the Lost Password form in the popup
    var $btn  = $form.find('button[type=submit], input[type=submit]').first();
    var label = ($btn.text() || $btn.val() || '').toLowerCase();

    if (label.indexOf('get new password') === -1 && label.indexOf('get new pass') === -1) {
      return; // not the reset form
    }

    e.preventDefault();

    // Try common input names used by RetroTube/WP
    var $input = $form.find('input[name=user_login], input[name=email], input[name=login], input[type=email], input[type=text]').first();
    var login = ($input.val() || '').trim();

    if (!login) {
      $form.find('.tmw-reset-msg').remove();
      $form.prepend('<p class="tmw-reset-msg" style="color:#f33;margin:0 0 10px;">' +
        'Please enter your username or email.' + '</p>');
      return;
    }

    $.post(window.tmwAuth.ajaxurl, {
      action: 'tmw_lostpass',
      login:  login,
      nonce:  window.tmwAuth.nonce || ''
    }, function(res){
      var msg = (res && res.data && res.data.message) ? res.data.message :
        'If an account exists, a reset email has been sent.';
      $form.find('.tmw-reset-msg').remove();
      $form.prepend('<p class="tmw-reset-msg alert alert-success" style="margin:0 0 10px;">' + msg + '</p>');
    });
  });
})(jQuery);
