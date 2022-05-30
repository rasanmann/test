#!/bin/bash

if kubeo yesno '[composer] Voulez-vous installer les dépendances PHP? (les dépendances sont committés au projet)'; then
    kubeo exec kubeo run composer install
fi

if [ ! -f docroot/sites/default/settings.local.php ]; then
    echo -ne "[config] Fichier de configuration"
    cp docroot/sites/default/settings.local.sample docroot/sites/default/settings.local.php
fi