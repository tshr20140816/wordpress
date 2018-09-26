#!/bin/bash

set -x

export TZ=JST-9

vendor/bin/heroku-php-apache2 -C apache.conf www
