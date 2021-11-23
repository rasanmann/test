#!/usr/bin/env bash

function composer {
    DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null && pwd )/../../"

    IMAGE=docker.gitlab.libeo.com/docker/php:7.3-composer

    docker pull ${IMAGE} &> /dev/null
    docker run --rm -u $(id -u):$(id -g) \
        -v "${DIR}"/.composer:/.composer \
        -v "${DIR}":/public \
        -it ${IMAGE} --working-dir=/public "$@" --ignore-platform-reqs
}
