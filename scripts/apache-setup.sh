#!/usr/bin/env bash
set -euo pipefail

APP_PATH=${1:-/var/www/climbsched}
VHOST_TARGET=${2:-/etc/apache2/sites-available/climbsched.conf}

SUDO=""
if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
  if command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
  else
    echo "Error: run as root or install sudo." >&2
    exit 1
  fi
fi

echo "Setting up Apache vhost for ClimbSched"
$SUDO cp "$APP_PATH/deploy/apache/climbsched.conf" "$VHOST_TARGET"
$SUDO sed -i "s|/var/www/climbsched|$APP_PATH|g" "$VHOST_TARGET"

$SUDO a2enmod rewrite
$SUDO a2ensite "$(basename "$VHOST_TARGET")"
$SUDO a2dissite 000-default.conf || true
$SUDO apache2ctl configtest
$SUDO service apache2 restart

echo "Done. Add hosts entry: 127.0.0.1 climbsched.local"
