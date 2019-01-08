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

httpd -V
# httpd -M | sort
php --version
# whereis php
# php -m
cat /proc/version
cat /proc/cpuinfo | grep 'model name' | head -n 1
curl --version
  
export USER_AGENT=$(curl https://raw.githubusercontent.com/tshr20140816/heroku-mode-07/master/useragent.txt)
echo $USER_AGENT

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

vendor/bin/heroku-php-apache2 -C apache.conf www
