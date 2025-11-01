# Investigation Report: Password Reset Links Returning `error=expiredkey`

## Summary
- Password reset emails send successfully, but following the link immediately triggers WordPress core's `error=expiredkey` response.
- No code changes were required; investigation focused on configuration, caching, and operational causes that invalidate reset tokens.
- The most likely culprit is duplicate reset generation (multiple submissions) or caching of `wp-login.php`; additional checks ensure no plugin alters the reset lifetime and that server time remains accurate.

## Observed Behaviour
1. Submitting the custom popup triggers a successful AJAX response and a reset email (confirmed by tester).
2. Visiting the reset URL (e.g. `/wp-login.php?action=rp&key=...&login=...`) redirects to `/wp-login.php?action=lostpassword&error=expiredkey` immediately.
3. WordPress core only returns `error=expiredkey` when the stored reset key is either expired or no longer matches the request parameters.

## Key Findings
- **Duplicate reset requests invalidate older emails.** WordPress stores only one active reset key per user. Any secondary submission—caused by double-clicking, auto-resubmission, or concurrent handlers—overwrites the prior key, rendering previous email links invalid.
- **Caching or optimisation of `wp-login.php` or reset query strings can interfere.** Cloudflare or performance plugins sometimes cache or modify login pages, causing stale validation results.
- **Filters can shrink the reset lifetime.** Themes/plugins may hook `password_reset_expiration`. If the filter returns `0` or another tiny value, WordPress treats every key as expired.
- **Legacy handlers might still run.** Multiple handlers firing for the same popup can create different keys in quick succession.
- **Email threading confusion remains possible.** Gmail threads all "Password Reset" emails, making it easy to click an older message with an invalidated key.
- **Server clock drift can force premature expiry.** WordPress compares the stored UNIX timestamp with the current server time. A skewed clock can make fresh keys appear expired.

## Verification Steps (No Code Changes)
### 1. Check for duplicate submissions
- Open browser DevTools → **Network** tab → filter to XHR.
- Submit the popup once and watch the requests.
  - ✅ Expect exactly **one** request to the lost-password AJAX endpoint.
  - ❌ If you see two requests (submit + resubmit/refresh), note the initiator script. Any duplicate submission means the first email will be invalid.
- Review mailbox timestamps. Multiple reset emails within seconds indicate duplicate keys. Always test using the newest email, ideally in an Incognito window.

### 2. Bypass caching and optimisation on login endpoints
- In Cloudflare (or other edge cache), create a rule for `*top-models.webcam/wp-login.php*`:
  - **Cache Level:** Bypass
  - **Rocket Loader:** Off
- In local optimisers (e.g. Autoptimize, WP Rocket), exclude the following URLs or query strings from minification/caching:
  - `wp-login.php`
  - Any URL containing `action=rp` or `action=lostpassword`
- Purge caches (Cloudflare + browser), request a new reset, and open the link from the freshest email in a private window.

### 3. Confirm the reset lifetime remains default
- Run via WP-CLI:
  ```bash
  wp eval 'var_dump( apply_filters("password_reset_expiration", DAY_IN_SECONDS ) );'
  ```
  - ✅ Expected output: `int(86400)`
  - ❌ If you see `0` or a very small value, a plugin/theme is altering the lifetime.
- To inspect the callbacks:
  ```bash
  wp eval 'print_r( $GLOBALS["wp_filter"]["password_reset_expiration"] ?? [] );'
  ```
  Identify the plugin/theme responsible and temporarily deactivate it to confirm.

### 4. Monitor for multiple handler executions
- Enable existing logging (if available) around the reset handler; look for duplicate "Password Reset" log lines per submission.
- In the database (`phpMyAdmin` or `wp db` commands), watch `wp_users.user_activation_key` for the target user:
  ```sql
  SELECT user_activation_key FROM wp_users WHERE user_login = 'USERNAME';
  ```
  Refresh a second after submitting. If the field changes twice, more than one handler is firing.

### 5. Ensure the newest email link is used
- In Gmail, open the latest message by timestamp or search `subject:"Password Reset" newer_than:1d`.
- Disable email client conversation collapse if necessary during testing.

### 6. Verify server clock accuracy
- Run via WP-CLI:
  ```bash
  wp eval 'echo "Server now: ".time()." (".gmdate("c").")\n";'
  ```
- Compare to actual UTC time. Large negative offsets or frequent jumps indicate NTP or clock issues that can invalidate tokens immediately.
- Ask hosting provider to confirm NTP synchronisation if discrepancies appear.

## Recommended Next Actions
1. **Re-test with caching bypassed and only one submission** to confirm whether duplicate requests or caching cause the invalidation.
2. **Use WP-CLI checks** to validate reset lifetime and server clock without altering code.
3. **Inspect plugin/theme filters** if lifetime deviates from 86400 seconds.
4. **Monitor logs or database updates** to ensure only one handler updates `user_activation_key` per request.

Following these steps should isolate the non-code factor leading to `error=expiredkey`. Once the offending behaviour (duplicate request, caching, filtered lifetime, or server time skew) is corrected operationally, reset links should work as expected without code modifications.
