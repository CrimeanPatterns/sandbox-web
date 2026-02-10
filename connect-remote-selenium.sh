#!/usr/bin/env bash

set -eux o pipefail
SELENIUM_HOST=192.168.4.191

ssh \
  -L localhost:11534:$SELENIUM_HOST:11534 \
  -L localhost:11539:$SELENIUM_HOST:11539 \
  -L localhost:11538:$SELENIUM_HOST:11538 \
\
  -L localhost:11594:$SELENIUM_HOST:11594 \
  -L localhost:11599:$SELENIUM_HOST:11599 \
  -L localhost:11598:$SELENIUM_HOST:11598 \
\
  -L localhost:11844:$SELENIUM_HOST:11844 \
  -L localhost:11849:$SELENIUM_HOST:11849 \
  -L localhost:11848:$SELENIUM_HOST:11848 \
\
  -L localhost:12994:$SELENIUM_HOST:12994 \
  -L localhost:12999:$SELENIUM_HOST:12999 \
  -L localhost:12998:$SELENIUM_HOST:12998 \
\
  -L localhost:13804:$SELENIUM_HOST:13804 \
  -L localhost:13809:$SELENIUM_HOST:13809 \
  -L localhost:13808:$SELENIUM_HOST:13808 \
\
  -L localhost:12954:$SELENIUM_HOST:12954 \
  -L localhost:12959:$SELENIUM_HOST:12959 \
  -L localhost:12958:$SELENIUM_HOST:12958 \
\
  -L localhost:12844:$SELENIUM_HOST:12844 \
  -L localhost:12849:$SELENIUM_HOST:12849 \
  -L localhost:12848:$SELENIUM_HOST:12848 \
\
  -L localhost:4444:$SELENIUM_HOST:4444 \
  -L localhost:43765:$SELENIUM_HOST:43765 \
\
  ssh.awardwallet.com