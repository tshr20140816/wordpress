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
  
# heroku-buildpack-php
current_version=$(cat composer.lock | grep version | awk '{print $2}' | tr -d ,)
composer update > /dev/null 2>&1 &

echo $current_version > /tmp/current_version

export USER_AGENT=$(curl https://raw.githubusercontent.com/tshr20140816/heroku-mode-07/master/useragent.txt)

htpasswd -c -b .htpasswd ${BASIC_USER} ${BASIC_PASSWORD}

pushd www
for file in $( ls . | grep .php$ ); do
  php -l ${file}
done
popd

wait

# heroku-buildpack-php
latest_version=$(cat composer.lock | grep version | awk '{print $2}' | tr -d ,)

echo "heroku-buildpack-php : current ${current_version} latest ${latest_version}"

echo $latest_version > /tmp/latest_version

vendor/bin/heroku-php-apache2 -C apache.conf www
