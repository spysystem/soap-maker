#!/bin/bash

set -e

printf "soap-maker installer\n\n"

printf "Creating basic script for building your SOAP client... "

wget -c https://github.com/spysystem/soap-maker/raw/master/resources/build.sh.sample -O build.sh
chmod 755 build.sh
printf "Done! \n"

printf "Creating a composer.json file to install soap-maker... "
wget -c https://github.com/spysystem/soap-maker/raw/master/resources/composer.json.sample -O composer.json
printf "Done! \n"

printf "Running composer... "
composer install
printf "Done! \n\n"

echo "soap-maker is ready to build your library, but you need to configure the newly created build.sh to fit your needs"