#!/bin/sh
# GoodTripLove deploy — run from the application directory on the server:
#   cd ~/apps/goodtriplove && sh deploy/deploy.sh
#
# Safe to re-run. It never touches anything outside the GoodTripLove
# application directory and its own document root.

set -e

PHP=${PHP:-/usr/local/php83/bin/php}
APP=$(cd "$(dirname "$0")/.." && pwd)
DOC=${DOC:-$HOME/domains/goodtriplove.com/public_html}

echo "app: $APP"
echo "doc: $DOC"

cd "$APP"

echo "--- code ---"
git fetch --quiet origin
git reset --hard origin/main --quiet
git log --oneline | head -1

echo "--- dependencies ---"
$PHP /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction --quiet

echo "--- database ---"
$PHP artisan migrate --force --no-interaction

echo "--- document root ---"
cp "$APP/deploy/index.php" "$DOC/index.php"
cp "$APP/deploy/public_html.htaccess" "$DOC/.htaccess"

for d in css js img; do
    rm -rf "$DOC/$d"
    ln -s "$APP/public/$d" "$DOC/$d"
done

for f in manifest.webmanifest sw.js favicon.ico; do
    if [ -f "$APP/public/$f" ]; then
        ln -sfn "$APP/public/$f" "$DOC/$f"
    fi
done

echo "--- permissions ---"
chmod -R 775 "$APP/storage" "$APP/bootstrap/cache"
chmod 600 "$APP/.env"

echo "--- caches ---"
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo "--- done ---"
$PHP artisan about --only=environment | head -8
