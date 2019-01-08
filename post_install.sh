#!/bin/bash

set -x

date

# ***** phppgadmin *****

pushd www
git clone --depth 1 https://github.com/phppgadmin/phppgadmin.git phppgadmin
cp ../config.inc.php phppgadmin/conf/
cp ../Connection.php phppgadmin/classes/database/
popd

wget https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.4.0/phpcs.phar
wget https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.4.0/phpcbf.phar

chmod 755 ./start_web.sh

date
