#!/bin/bash
set -exo pipefail

if [[ "$SYMFONY_ENV" == "" ]]; then
  SYMFONY_ENV=dev
fi
if [[ "$SYMFONY_DEBUG" == "" ]]; then
  SYMFONY_DEBUG=1
fi

cp /etc/nginx/conf.d/default.conf.template /etc/nginx/conf.d/default.conf
sed -i "s/%SYMFONY_ENV%/$SYMFONY_ENV/" /etc/nginx/conf.d/default.conf
cp /etc/nginx/conf.d/default.conf /etc/nginx/conf.d/live.conf.template
exec nginx -g "daemon off;";