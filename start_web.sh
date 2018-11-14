#!/bin/bash

set -x

export TZ=JST-9

if [ ! -v BASIC_USER ]; then
  echo "Error : BASIC_USER not defined."
  exit
fi

if [ ! -v BASIC_PASSWORD ]; then
  echo "Error : BASIC_PASSWORD not defined."
  exit
fi

current_version=$(cat composer.lock | grep version | awk '{print $2}' | tr -d ,)
composer update > /dev/null 2>&1 &

export USER_AGENT=$(curl https://raw.githubusercontent.com/tshr20140816/heroku-mode-07/master/useragent.txt)

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

wait
# heroku-buildpack-php
new_version=$(cat composer.lock | grep version | awk '{print $2}' | tr -d ,)

if [ $current_version = $new_version ]; then
  echo "heroku-buildpack-php : latest version"
else
  echo "heroku-buildpack-php : old version"
fi

vendor/bin/heroku-php-apache2 -C apache.conf www
