#!/usr/bin/env bash

set -euxo pipefail

cleanup ()
{
  echo "received sigterm, exiting"
  kill -s SIGTERM $!
  exit 0
}

trap cleanup SIGINT SIGTERM

while [ -n "1" ]; do
  /usr/lib/php/sessionclean
  sleep 600 &
  wait $!
done