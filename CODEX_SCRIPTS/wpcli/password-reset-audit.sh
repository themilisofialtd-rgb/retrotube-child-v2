#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# Password Reset Audit — WP-CLI Command Reference (no automatic execution)
# -----------------------------------------------------------------------------
# This file is intentionally non-executable. Copy the commands you need and run
# them from the WordPress install directory. Each command is independent.
# -----------------------------------------------------------------------------

# 1) Confirm the current password reset lifetime (expect int(86400)).
# wp eval 'var_dump( apply_filters("password_reset_expiration", DAY_IN_SECONDS ) );'

# 2) Inspect callbacks attached to password_reset_expiration.
# wp eval 'print_r( $GLOBALS["wp_filter"]["password_reset_expiration"] ?? [] );'

# 3) Capture the server clock in UNIX time + ISO8601 for drift analysis.
# wp eval 'echo "Server now: ".time()." (".gmdate("c").")\n";'

# 4) (Optional) Watch the activation key directly for a user (replace USERNAME).
# wp db query "SELECT user_activation_key FROM wp_users WHERE user_login = 'USERNAME';"

# 5) (Optional) Clear stale reset keys for a test account before retrying.
# wp db query "UPDATE wp_users SET user_activation_key = '' WHERE user_login = 'USERNAME';"
