#!/bin/bash

set -x

date

# ***** phppgadmin *****

pushd www
git clone --depth 1 https://github.com/phppgadmin/phppgadmin.git phppgadmin
cp ../config.inc.php phppgadmin/conf/
cp ../Connection.php phppgadmin/classes/database/
popd

pushd /temp

wget https://ja.wordpress.org/wordpress-5.0.2-ja.tar.gz
tar xf wordpress-5.0.2-ja.tar.gz

ls -lang

popd

chmod 755 ./start_web.sh

date
