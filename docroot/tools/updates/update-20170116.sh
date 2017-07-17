#!/usr/bin/env bash

# Set website in maintenance mode
drush sset system.maintenance_mode TRUE

# Lock cron
drush sset system.cron_lock locked

# Fix html_title error
drush sql-query "DELETE FROM key_value WHERE collection='system.schema' AND name='html_title';"

# Update database and entities
drush updatedb

# Enable new modules
drush pm-enable email_registration rest restui serialization yqb_api yqb_helpdesk -y
drush pm-enable yqb_app_beacon yqb_app_page_mobile yqb_app_question yqb_app_user yqb_app_user_flight yqb_app_migrate yqb_app_rest_resource -y

# Flush questions
drush pm-enable delete_all -y
drush delete-all-delete-content --type question
drush pm-uninstall delete_all -y

# Migrate content
drush mi zones
drush mi tips
drush mi beacons
drush mi pages_mobiles
drush mi questions

# Add anonymous user permissions
drush role-add-perm 'anonymous' "restful delete user_flight_rest_resource, restful get airline_rest_resource, restful get airport_rest_resource, restful get flight_rest_resource, restful get parking_rest_resource, restful get user_flight_rest_resource, restful get user_rest_resource, restful get wait_rest_resource, restful get zone_rest_resource, restful patch user_flight_rest_resource, restful patch user_rest_resource, restful post alert_rest_resource, restful post flight_rest_resource, restful post parking_rest_resource, restful post user_flight_rest_resource, restful post user_login_rest_resource, restful post user_logout_rest_resource, restful post user_password_rest_resource, restful post user_rest_resource, restful post zone_rest_resource"

# Add authenticated user permissions
drush role-add-perm 'authenticated' "restful delete user_flight_rest_resource, restful get airline_rest_resource, restful get airport_rest_resource, restful get flight_rest_resource, restful get parking_rest_resource, restful get user_flight_rest_resource, restful get user_rest_resource, restful get wait_rest_resource, restful get zone_rest_resource, restful patch user_flight_rest_resource, restful patch user_rest_resource, restful post alert_rest_resource, restful post flight_rest_resource, restful post parking_rest_resource, restful post user_flight_rest_resource, restful post user_login_rest_resource, restful post user_logout_rest_resource, restful post user_password_rest_resource, restful post user_rest_resource, restful post zone_rest_resource"

# Questions
drush php-eval "\Drupal\views\Views::getView('questions')->storage->delete();"
drush pm-enable yqb_app_views -y

# Clean up
drush pm-uninstall yqb_app_beacon yqb_app_page_mobile yqb_app_question yqb_app_user yqb_app_user_flight yqb_app_migrate yqb_app_rest_resource yqb_app_views -y

# Unlock cron
drush sdel system.cron_lock

# Make website available
drush sset system.maintenance_mode FALSE