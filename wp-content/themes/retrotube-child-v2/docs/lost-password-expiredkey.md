# Lost Password “expiredkey” — Runbook (No Code Changes)

## What it means
WordPress shows `error=expiredkey` when the stored reset key is missing/old OR its expiration timestamp is already past. Clicking an older email after a newer request also yields `expiredkey`.

## Quick path (try first)
1) Request ONE new reset and only click the **newest** email in a private window.
2) Ensure caching/optimization never touches `wp-login.php`:
   - Cloudflare Page Rule: URL pattern `*top-models.webcam/wp-login.php*`
     • Cache Level: Bypass  
     • Rocket Loader: Off (on this URL only)
   - Any page/asset optimizer: exclude `wp-login.php` and querystrings `action=rp|lostpassword`.
3) If still failing, use the CLI helper in this repo:
   - `wp eval-file wp-content/themes/retrotube-child-v2/CODEX_TOOL_resetkey_diag.php <username|email>`
   - Check `rp_key` exists and `rp_key_expiration` is in the future.
   - If stale, clear and retry:
     `wp eval-file wp-content/themes/retrotube-child-v2/CODEX_TOOL_resetkey_diag.php <user> -- --reset=1`

## Deep checks
- Confirm effective lifetime:
  `wp eval 'var_dump( apply_filters("password_reset_expiration", DAY_IN_SECONDS ) );'`
  Expected: `int(86400)`. If 0 or small, a plugin/theme is forcing instant expiry.
- If using object cache, temporarily disable and retry.
- Ensure only one reset handler runs; multiple calls to `retrieve_password()` can invalidate earlier links.

## Acceptance
1) New request → newest email only → click once (incognito) → see “Set New Password” form.
2) Re-clicking same link shows `invalidkey` (expected single-use).
