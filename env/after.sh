#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null && pwd )"

echo -ne "├─ Vidage de la cache "
$DIR/../drupal cr &> /dev/null
kubeo status $?

echo -ne "├─ Voulez vous synchroniser la BD? [o/N] "
read sync_bd

if [ "$sync_bd" == "o" -o "$sync_bd" == "O" ]; then
    kubeo sync
fi