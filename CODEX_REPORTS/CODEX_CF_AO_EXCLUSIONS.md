# Cloudflare & Autoptimize Exclusions — Password Reset Stability

## Cloudflare Page Rule
Apply a single rule targeting the login endpoint:

```json
{
  "targets": [
    {
      "target": "url",
      "constraint": {
        "operator": "matches",
        "value": "*top-models.webcam/wp-login.php*"
      }
    }
  ],
  "actions": {
    "cache_level": "bypass",
    "rocket_loader": "off",
    "edge_cache_ttl": 0
  },
  "priority": 1,
  "status": "active"
}
```

**Operator steps:**
1. Log into Cloudflare → Rules → Page Rules → **Create Page Rule**.
2. Pattern: `*top-models.webcam/wp-login.php*`
3. Settings:
   - Cache Level → **Bypass**
   - Rocket Loader → **Off**
   - (Optional) Disable Apps if any are attached.
4. Save → Deploy → Purge Cache (Everything).

## Autoptimize (or Similar Optimisers)
Add the following entries to the exclusion fields and clear caches afterwards.

- **JS optimisation exclusion:**
  ```
  wp-login.php,action=rp,action=lostpassword
  ```
- **CSS/HTML exclusion (if available):**
  ```
  wp-login.php
  ```
- **Critical CSS / Preload filters:** ensure any rule targeting `/wp-login.php` is disabled.

**Operator steps:**
1. Navigate to WP Admin → Settings → Autoptimize.
2. Expand **JS, CSS & HTML** options.
3. Paste the exclusions above into the appropriate fields (separate by comma).
4. Click **Save Changes and Empty Cache**.
5. If WP Rocket or similar is present, add the same paths/parameters to its URL exclusion list and purge caches.

> These exclusions prevent cached or minified responses from invalidating freshly generated password reset keys.
