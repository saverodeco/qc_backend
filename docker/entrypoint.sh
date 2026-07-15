#!/usr/bin/env bash
set -e

# Render menyuntikkan PORT lewat environment variable saat runtime — nilainya
# bisa beda tiap deploy, jadi listen port nginx di-generate saat container
# start, bukan di-hardcode waktu build image.
: "${PORT:=10000}"
envsubst '${PORT}' < /etc/nginx/sites-available/default > /etc/nginx/sites-available/default.tmp
mv /etc/nginx/sites-available/default.tmp /etc/nginx/sites-available/default

cd /var/www/html

# Cache config/route/view — aman dijalankan ulang tiap start, hasil lama
# otomatis ketimpa.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migration wajib --force karena APP_ENV=production (tanpa ini Laravel akan
# nanya konfirmasi interaktif dan container akan macet karena tidak ada TTY).
php artisan migrate --force

# storage:link aman dijalankan berkali-kali — kalau symlink sudah ada,
# Laravel skip tanpa error.
php artisan storage:link || true

exec "$@"