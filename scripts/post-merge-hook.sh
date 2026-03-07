#!/bin/sh
# Git post-merge hook — installed to .git/hooks/post-merge by UPDATE_SITE.sh.
#
# Writes the short commit hash to storage/app/version.txt after every
# successful 'git pull' so the page footer always reflects the current
# deployed commit without requiring a manual UPDATE_SITE.sh run.
#
# The app_version cache key must also be cleared for the change to appear:
#   php artisan cache:forget app_version
# UPDATE_SITE.sh handles this; subsequent auto-pulls via cron would need
# a separate cache-clear step if used.

git rev-parse --short HEAD > "$(git rev-parse --show-toplevel)/storage/app/version.txt"
