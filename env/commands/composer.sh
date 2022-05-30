#!/usr/bin/env bash

function composer {
    IMAGE="docker.gitlab.libeo.com/docker/php:7.4-fpm"

    COMPOSER_CACHE_DIR="/.composer"
    COMPOSER_CACHE_VOLUME="${KUBEO_PROJECT_NAME}_composer_cache"

    docker volume create $COMPOSER_CACHE_VOLUME &> /dev/null || true
    docker pull "$IMAGE" &> /dev/null
    docker run --rm \
        -e CURRENT_UID=$(id -u) \
        -e COMPOSER_CACHE_DIR=${COMPOSER_CACHE_DIR} \
        -v "${COMPOSER_CACHE_VOLUME}:${COMPOSER_CACHE_DIR}" \
        -v "$PWD":/src \
        -w /src \
        -it "$IMAGE" composer "$@"
}
