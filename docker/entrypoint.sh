#!/bin/sh
set -e

mkdir -p \
    storage/app/livewire-tmp \
    storage/app/private \
    storage/app/private/imports \
    storage/app/private/livewire-tmp \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
