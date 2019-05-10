# YQB

## Dépendances

- [Kubeo](https://gitlab.libeo.com/libeo/kubeo)

## Ajouter sa clé ssh dans Acquia

<https://cloud.acquia.com/app/profile/ssh-keys>

## Installation

- `kubeo start`


- Note: Il se peut que le premier chargement du site web résulte en une erreur. Simplement rafraîchir une deuxième fois résout le problème.

## Commandes utiles

- `kubeo sync`: permet de synchroniser la base de données et les fichiers uploads

## Créer un compte admin avec drush

Remplacer [USERNAME], [EMAIL] et [PASSWORD] et exécuter ces commandes:

```bash
./drupal user-create [USERNAME] --mail="[EMAIL]" --password="[PASSWORD]"
./drupal user-add-role "administrator" [USERNAME]
```

## Vider la cache

Toujours dans le conteneur, vides la cache drupal avec cette commande

```bash
./drupal cr
```


## Accéder au site

- http://yqb.local.vici.io/fr
- Connexion: http://yqb.local.vici.io/fr/user

## Déploiements

Voir sur le site de [Acquia](https://cloud.acquia.com)
