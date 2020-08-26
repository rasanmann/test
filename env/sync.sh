#!/bin/bash
echo "├─ Valeurs possibles: "
echo "├─ aeroportdequebec.com"
echo "├─ yqbdev.ssh.prod.acquia-sites.com"
echo -ne "├─ À partir de quelle serveur ? [aeroportdequebec.com] "
read server

if [ "$server" == "" ]; then
    server="aeroportdequebec.com"
fi

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null && pwd )"

if [ "$server" == "aeroportdequebec.com" ]; then
    ssh yqb.prod@yqb.ssh.prod.acquia-sites.com -p 22 "/var/www/html/yqb.prod/vendor/drush/drush/drush sql-dump" | docker exec -i yqb_mysql mysql -u yqb -pyqb yqb &> /dev/null

    echo -ne "├─ Voulez-vous copier les uploads? [o/N] "
    read copy_uploads

    if [ "$copy_uploads" == "o" -o "$copy_uploads" == "O" ]; then
        if [ ! -f $DIR/../docroot/sites/default/files ]; then
            mkdir $DIR/../docroot/sites/default/files &> /dev/null
        fi
        rsync -rauve "ssh -p 22" yqb.prod@yqb.ssh.prod.acquia-sites.com:/var/www/html/yqb.prod/docroot/sites/default/files/* $DIR/../docroot/sites/default/files/. &> /dev/null
    fi
elif [ "$server" == "yqbdev.ssh.prod.acquia-sites.com" ]; then
    ssh yqb.dev@yqbdev.ssh.prod.acquia-sites.com -p 22 "/var/www/html/yqb.dev/vendor/drush/drush/drush sql-dump" | docker exec -i yqb_mysql mysql -u yqb -pyqb yqb &> /dev/null

    echo -ne "├─ Voulez-vous copier les uploads? [o/N] "
    read copy_uploads

    if [ "$copy_uploads" == "o" -o "$copy_uploads" == "O" ]; then
        if [ ! -f $DIR/../docroot/sites/default/files ]; then
            mkdir $DIR/../docroot/sites/default/files &> /dev/null
        fi
        rsync -rauve "ssh -p 22" yqb.dev@yqbdev.ssh.prod.acquia-sites.com:/var/www/html/yqb.dev/docroot/sites/default/files/* $DIR/../docroot/sites/default/files/. &> /dev/null
    fi
fi