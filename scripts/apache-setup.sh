#!/usr/bin/env bash
set -euo pipefail

APP_PATH=${1:-/var/www/climbsched}
VHOST_TARGET=${2:-/etc/apache2/sites-available/climbsched.conf}

echo "Setting up Apache vhost for ClimbSched"
sudo cp "$APP_PATH/deploy/apache/climbsched.conf" "$VHOST_TARGET"
sudo sed -i "s|/var/www/climbsched|$APP_PATH|g" "$VHOST_TARGET"

sudo a2enmod rewrite
sudo a2ensite climbsched.conf
sudo a2dissite 000-default.conf || true
sudo apache2ctl configtest
sudo systemctl reload apache2

echo "Done. Add hosts entry: 127.0.0.1 climbsched.local"
