#!/bin/bash

set -x

wget https://cli-assets.heroku.com/heroku-cli/channels/stable/heroku-cli-linux-x64.tar.gz -O heroku.tar.gz
tar xvfz heroku.tar.gz
rm heroku.tar.gz

echo 'https://devcenter.heroku.com/articles/dyno-metadata'
