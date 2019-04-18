#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null && pwd )"

echo -ne "├─ [composer install] Installation des dépendances PHP"
$DIR/../composer install
kubeo status $?
$DIR/../composer install
kubeo status $?

if [ ! -f $DIR/../docroot/sites/default/settings.local.php ]; then
    echo -ne "├─ Fichier de configuration"
    cp $DIR/../docroot/sites/default/settings.local.sample $DIR/../docroot/sites/default/settings.local.php
fi