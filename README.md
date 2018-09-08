# YQB

## Setup container

Aller dans dossier /outils/docker

```bash
docker-compose up -d
```

## Importer la bd

Se connecter au container database:

```bash
docker exec -i -t yqb_database_1 bash
```

Ensuite, importer la bd:

```bash
mysql -u drupal -pdrupal drupal < /bd/20180522-dev.sql
```

\* todo - ajouter commande drush pour syncroniser bd avec prod/dev/stage

## Ajouter sa clé ssh dans acquia

<https://cloud.acquia.com/app/profile/ssh-keys>

## Copier les fichiers (sites/default/files)

À partir du dossier `docker/`, faire la commande suivante pour copier les fichiers du dev vers la vm.

```bash
rsync -avz -e "ssh -p 22" yqb.dev@staging-25038.prod.hosting.acquia.com:/var/www/html/yqbdev/docroot/sites/default/files/ ../../docroot/sites/default/files
```

Écrire "yqb.`{dev|stage|prod}`@staging-25038.prod.hosting.acquia.com" pour avoir les fichiers de l'environnement correspondant

## Composer install

On doit être connecté au container php avec l'utilisateur docker et être dans le dossier /site/docroot

```bash
docker exec -i -t --user docker yqb_php_1 bash
cd /site/docroot
composer install
```

## Npm install

On doit être connecté au container php avec l'utilisateur docker et être dans le dossier /site/docroot

```bash
docker exec -i -t --user docker yqb_php_1 bash
cd /site/docroot
npm install
```

\* todo - voir comment la compilation du sass fonctionne. On dirait qu'il n'y a rien pour ceci dans le grunt actuel.
todo - voir aussi les autres commandes grunt et leurs utilisations

## Fichier de config

Faire une copie du fichier "/docroot/sites/default/settings.local.sample" et le nommer "settings.local.php" (dans le même dossier)

## Créer un compte admin avec drush

Remplacer username, email et mot de passe et exécuter ces commandes drush dans le container php:

```bash
drush user-create [USERNAME] --mail="[EMAIL]" --password="[PASSWORD]"
drush user-add-role "administrator" [USERNAME]
```

## Vider la cache

Toujours dans le conteneur, vides la cache drupal avec cette commande

```bash
drush cr
```


## Accéder au site

- http://localhost:8989/fr
- Connexion: http://localhost:8989/fr/user

## Cron

Pour que les cron roulent sur le site, on peut seulement activer le module "Automated cron" à l'url <http://localhost:8989/fr/admin/modules>.
De cette façon, les cron seront exécutées automatiquement lors de la navigation sur le site.

*** finalement, je ne suis pas encore certain que ça fonctionne de cette façon

## Déploiements

Voir sur le site de [Acquia](https://cloud.acquia.com)
