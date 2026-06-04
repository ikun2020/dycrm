ARG APP_IMAGE=dycrm-app:latest

FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
