#!/usr/bin/env bash

function drush {
    # TODO: How to manage the fact that this requires MySQL more elegantly
    docker exec -u root -i yqb_php-fpm apk add mysql-client &> /dev/null
    docker exec -u root yqb_php-fpm bash -c "mkdir -p /home/app/ && chown app: /home/app" &> /dev/null
    docker exec -w /app/ -u app -i yqb_php-fpm /app/vendor/drush/drush/drush "$@"
}