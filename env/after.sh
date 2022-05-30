#!/usr/bin/env bash

if kubeo yesno '[sync] Voulez-vous synchroniser la BD?'; then
    kubeo sync
fi

echo -ne "[drupal] Vidage de la cache "
kubeo exec kubeo run drush cr