# YQB

## Setup container
Aller dans dossier /outils/docker
```
docker-compose up -d
```

## Importer la bd
Se connecter au container database:
```
docker exec -i -t yqb_database_1 bash
```
Ensuite, importer la bd:
```
mysql -u drupal -pdrupal drupal < /bd/20180522-dev.sql
```

todo - ajouter commande drush pour syncroniser bd avec prod/dev/stage

## Composer install
On doit être connecté au container php avec l'utilisateur docker et être dans le dossier /site
```
docker exec -i -t --user docker yqb_php_1 bash
cd /site/docroot
composer install
```


## Copier les fichiers (sites/default/files)
rsync -avz -e ssh yqb.dev@staging-25038.prod.hosting.acquia.com:/var/www/html/yqbdev/docroot/sites/default/files/ /site/docroot/sites/default/files



## Autre
\* S'assurer que memcached est bien démarré dans container yqb_php_1


## Créer un compte admin avec drush
drush user-create gary --mail="gary.deschenes@libeo.com" --password="gary"
drush user-add-role "administrator" gary

