#!/bin/bash

set -x

if [ ! -v APP_NAME ]; then
  echo "Error : APP_NAME not defined."
  exit
fi

./heroku pg:backups schedule DATABASE_URL --at '05:00 Asia/Tokyo' --app ${APP_NAME}
