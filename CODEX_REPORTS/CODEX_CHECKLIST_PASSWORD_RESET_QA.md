# Password Reset `error=expiredkey` QA Checklist

Use this list after applying the Cloudflare & Autoptimize exclusions. Tick each item during the audit.

- [ ] Cloudflare Page Rule for `*top-models.webcam/wp-login.php*` set to **Cache Level: Bypass** and **Rocket Loader: Off**.
- [ ] Cloudflare cache purged after saving the rule.
- [ ] Autoptimize (or equivalent optimiser) excludes `wp-login.php`, `*action=rp*`, and `*action=lostpassword*` from optimisation/caching.
- [ ] Autoptimize (or equivalent) cache cleared.
- [ ] Browser cache cleared or Incognito window opened for testing.
- [ ] DevTools → Network (XHR) tab open before submitting the lost-password popup.
- [ ] Popup submitted once; exactly one `admin-ajax.php` request observed.
- [ ] Only one new password reset email received for the test user.
- [ ] Reset link opened within 60 seconds in Incognito → password reset form loads (no redirect).
- [ ] `wp eval 'var_dump( apply_filters("password_reset_expiration", DAY_IN_SECONDS ) );'` returns `int(86400)`.
- [ ] `wp eval 'print_r( $GLOBALS["wp_filter"]["password_reset_expiration"] ?? [] );'` output recorded (empty or list of callbacks).
- [ ] `wp eval 'echo "Server now: ".time()." (".gmdate("c").")\n";'` matches real UTC within a few seconds.
- [ ] Second reset cycle repeated with the same success result.

> If any box cannot be checked, record the blocker and halt further password reset attempts until resolved.
