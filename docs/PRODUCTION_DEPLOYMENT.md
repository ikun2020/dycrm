# Production Deployment Memory

This project is deployed from Docker images built by GitHub Actions.

## Source Of Truth

- Local development and testing can happen on this machine.
- Production deploys should not rebuild the app image on the VPS by default.
- Pushing to GitHub `main` triggers `.github/workflows/docker-image.yml`.
- The workflow builds `docker/php/Dockerfile` and pushes the app image to GitHub Container Registry.
- The default production image is:

```text
ghcr.io/ikun2020/dycrm:latest
```

## VPS Update Command

Use this flow when updating the VPS from the latest GitHub image:

```bash
cd /www/wwwroot/dycrm

git fetch origin
git checkout main
git pull origin main

docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d --force-recreate

docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan shield:generate --all --panel=admin --no-interaction
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=ShieldRoleSeeder --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec app sh -lc 'chmod -R ug+rw storage bootstrap/cache'
docker compose -f docker-compose.prod.yml exec app php artisan optimize
docker compose -f docker-compose.prod.yml exec app php artisan queue:restart
```

The `shield:generate` and `ShieldRoleSeeder` steps are required after permission-related updates so Shield roles and permissions stay in sync. The `optimize:clear`, `chmod`, `optimize`, and `queue:restart` steps are important after image updates. They prevent stale Laravel/Filament compiled views, cache, storage permissions, or queue workers from keeping old code loaded.

## When To Build On VPS

Only use this on the VPS when intentionally bypassing GitHub Actions or debugging image builds:

```bash
docker compose -f docker-compose.prod.yml build
```

Do not replace the normal production update flow with VPS `build` unless the user explicitly asks for local VPS builds.

## Safety Notes

- Back up the database before large migrations or risky releases.
- Keep production `.env` secrets only on the VPS.
- Do not commit `.env` files or API tokens.
- `docker-compose.prod.yml` should use the app image configured by `APP_IMAGE`.
