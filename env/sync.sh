#!/usr/bin/env bash

echo "[sync] Valeurs possibles: "
echo "[sync] aeroportdequebec.com"
echo "[sync] yqbdev.ssh.prod.acquia-sites.com"
echo -ne "[sync] À partir de quelle serveur ? [aeroportdequebec.com] "
read server

if [ "$server" = "" ]; then
    server="aeroportdequebec.com"
fi

if [ "$server" = "aeroportdequebec.com" ]; then
    SOURCE_FOLDER="/var/www/html/yqb.prod/docroot/sites/default/files/"
    DRUSH_COMMAND="/var/www/html/yqb.prod/vendor/drush/drush/drush"
    SERVER="yqb.prod@yqb.ssh.prod.acquia-sites.com"
elif [ "$server" = "yqbdev.ssh.prod.acquia-sites.com" ]; then
    SOURCE_FOLDER="/var/www/html/yqb.dev/docroot/sites/default/files/"
    DRUSH_COMMAND="/var/www/html/yqb.dev/vendor/drush/drush/drush"
    SERVER="yqb.dev@yqbdev.ssh.prod.acquia-sites.com"
fi

echo -ne "[sync:db] Synchronisation de la base de données"
ssh $SERVER -p22 "$DRUSH_COMMAND sql-dump" | docker exec -i ${KUBEO_PROJECT_NAME}_mysql mysql -u ${KUBEO_PROJECT_NAME} -p${KUBEO_PROJECT_NAME} ${KUBEO_PROJECT_NAME} &> /dev/null
kubeo status $?

if kubeo yesno '[sync] Voulez-vous copier les uploads?'; then
    if [ ! -d docroot/sites/default/files ]; then
        mkdir docroot/sites/default/files &> /dev/null
    fi

    echo -ne "[sync:file] Synchronisation des fichiers"
    rsync -rauve "ssh -p22" $SERVER:$SOURCE_FOLDER docroot/sites/default/files/ &> /dev/null
    kubeo status $?
fi