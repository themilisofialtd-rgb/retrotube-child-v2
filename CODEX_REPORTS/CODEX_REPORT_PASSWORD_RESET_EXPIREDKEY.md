# TMW Password Reset `error=expiredkey` Audit Report

**Revision:** 2025-11-01 18:07 UTC  
**Scope:** Documentation-only investigation. No executable code was modified.

## 1. Observed Behaviour (from tester screenshots)
- The lost-password popup returns a green success message immediately after submission.
- A password reset email arrives in the mailbox within seconds.
- Clicking the newest reset link (`/wp-login.php?action=rp&key=…&login=…`) redirects straight back to `/wp-login.php?action=lostpassword&error=expiredkey` instead of showing the reset form.

## 2. Repository Search Findings (no code changes)
_All searches were run at the project root with `rg`. Context shown is 5–10 lines around each hit._

### `password_reset_expiration`
- `wp-content/themes/retrotube-child-v2/CODEX_TOOL_resetkey_diag.php`
  ```php
  $lifesec = apply_filters('password_reset_expiration', DAY_IN_SECONDS);
  echo "Lifetime(flt password_reset_expiration) : " . var_export($lifesec, true) . "\n";
  ```
- `inc/tmw-lostpass-bulletproof.php` *(no hooks altering lifetime; only calls `retrieve_password()`)*
  ```php
  // Call WP core
  add_filter('allow_password_reset', function ($allow, $uid) {
      error_log('[TMW-LOSTPASS-AUDIT] filter:allow_password_reset user_id=' . intval($uid) . ' allow=' . var_export($allow, true));
      return $allow;
  }, 10, 2);
  …
  $result = retrieve_password($login);
  ```
- Documentation references only (`CODEX_REPORT_PASSWORD_RESET_EXPIREDKEY.md`, `docs/lost-password-expiredkey.md`).

> **Conclusion:** No theme code filters `password_reset_expiration`. Runtime files only read the filter or call core helpers.

### `retrieve_password_message`
- **No occurrences found.**

### `retrieve_password_title`
- **No occurrences found.**

### `retrieve_password(`
- `inc/tmw-lostpass-bulletproof.php`
  ```php
  // If email was provided, translate to user_login for retrieve_password() compatibility
  if ($is_email) {
      $user = get_user_by('email', $identifier);
      if ($user && $user instanceof WP_User) {
          $login = $user->user_login;
      }
  }

  $result = retrieve_password($login);
  ```
- Documentation reminder in `docs/lost-password-expiredkey.md` (informational only).

### `lostpassword_post`
- **No occurrences found.**

### `check_password_reset_key`
- **No occurrences found.**

### `tmw_lostpass_bp_handle`
- `inc/tmw-lostpass-bulletproof.php`
  ```php
  add_action('wp_ajax_nopriv_tmw_lostpass_bp', 'tmw_lostpass_bp_handle', 0);
  add_action('wp_ajax_tmw_lostpass_bp', 'tmw_lostpass_bp_handle', 0);

  function tmw_lostpass_bp_handle()
  {
      nocache_headers();
      …
      $result = retrieve_password($login);
      …
  }
  ```

### `tmw-lostpass-bridge.js`
- `functions.php`
  ```php
  wp_enqueue_script(
      'tmw-lostpass-bridge',
      get_stylesheet_directory_uri() . '/js/tmw-lostpass-bridge.js',
      ['jquery'],
      '1.4.0',
      true
  );
  ```
- `js/tmw-lostpass-bridge.js`
  ```javascript
  $form.on('submit', function (e) {
    e.preventDefault();
    …
    $.post(window.ajaxurl || '/wp-admin/admin-ajax.php', data)
      .done(function (res) {
        …
      })
      .always(function () {
        setLoading(false);
      });
  });
  ```

> **Conclusion:** Only the bulletproof handler owns the AJAX endpoint. No duplicate PHP handlers were found in the repo, so duplicate keys would come from multiple submissions, not extra hooks.

## 3. Duplicate-Submit Risk Analysis
1. Open the lost-password popup, then open **DevTools → Network → XHR**.
2. Submit the popup **once** and watch the request list.
   - ✅ Expected: exactly one `admin-ajax.php` request (action `wpst_lostpassword` or `tmw_lostpass_bp`).
   - ❌ If a second request appears (due to double-click, auto-retry, or form submit + AJAX), the first reset key becomes invalid immediately.
3. Inspect the Network entry details → **Initiator** column to identify any script triggering extra posts (`tmw-lostpass-bridge.js` should appear only once).
4. After the test, check the mailbox timestamps. Multiple reset emails within the same minute confirm duplicate submissions. Always click the latest message.

## 4. Caching & Optimisation Risks (operator actions only)
1. **Cloudflare Page Rule** for `*top-models.webcam/wp-login.php*`:
   - Cache Level: **Bypass**
   - Rocket Loader: **Off**
   - Edge Cache TTL: **Respect Existing Headers** (default)
2. **Autoptimize / WP Rocket / similar**:
   - Exclude `wp-login.php`, `*action=rp*`, and `*action=lostpassword*` from HTML/JS/CSS minification & caching.
   - Clear plugin caches after saving exclusions.
3. Purge Cloudflare cache (Page Rule already bypasses future hits) and clear the browser cache. Re-request a reset link in an Incognito window.

## 5. Reset Lifetime Verification (WP-CLI only; no code edits)
Run these commands from the WordPress root:
```bash
wp eval 'var_dump( apply_filters("password_reset_expiration", DAY_IN_SECONDS ) );'
wp eval 'print_r( $GLOBALS["wp_filter"]["password_reset_expiration"] ?? [] );'
```
- Expected lifetime: `int(86400)` (24 hours).
- If callbacks appear in the second command, capture their hooked functions for follow-up.

## 6. Server Clock Sanity Check (WP-CLI)
```bash
wp eval 'echo "Server now: ".time()." (".gmdate("c").")\n";'
```
- Compare the UNIX timestamp and ISO8601 output with actual UTC (e.g., https://time.is/UTC).
- Any drift greater than a few seconds warrants hosting support to confirm NTP sync.

## 7. Mailbox Hygiene Reminder
- Gmail threads reset emails together. Expand the thread and click the newest message only.
- Delete/archive old reset messages during testing to avoid accidental clicks.

## 8. Acceptance Tests for the Operator
1. Apply the Cloudflare and Autoptimize exclusions; purge caches.
2. Open DevTools → Network (XHR) and confirm only one request fires when submitting the popup.
3. Trigger a reset; verify that exactly one new email arrives.
4. Within 60 seconds, open the newest email’s link in an Incognito window → expect the reset form (no redirect).
5. Repeat the cycle twice to ensure stability.
6. Capture outputs from the WP-CLI lifetime and clock checks for the audit log.

> If issues persist after these steps, escalate with the collected evidence (repo grep results, WP-CLI output, caching configuration screenshots) before considering any PHP-level logging changes in a follow-up PR.
