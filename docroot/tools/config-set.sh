#!/usr/bin/env bash

ENVIRONMENT="$1"

cat sites/default/files/config/$ENVIRONMENT/moneris.settings.yml | drush config-set --format=yaml -y --verbose moneris.settings moneris -
cat sites/default/files/config/$ENVIRONMENT/advam.settings.yml | drush config-set --format=yaml -y --verbose advam.settings advam -
cat sites/default/files/config/$ENVIRONMENT/twilio.settings.yml | drush config-set --format=yaml -y --verbose twilio.settings twilio -