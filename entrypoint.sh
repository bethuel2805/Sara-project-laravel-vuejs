#!/bin/bash
set -e

# Appliquer les migrations Laravel
php artisan migrate --force

# Vider et recréer les caches pour éviter les erreurs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Lancer Apache en avant-plan
exec "$@"
