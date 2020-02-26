<?php

function yqb_delete_files_drush_command() {
  $items = [];
  $items['yqb-delete-files'] = [
    'description' => 'Delete temporary files that do not exist.',
    'drupal dependencies' => ['yqb_delete_files'],
    'aliases' => ['yqb:delete-files', 'yqb:delf'],
  ];

  return $items;
}

function drush_yqb_delete_files_yqb_delete_files() {
  $deleted_files_count = \Drupal::service('yqb_delete_files.files')->cleanUp();
  drush_print(\Drupal::translation()->formatPlural($deleted_files_count, '1 file deleted.', '@count files deleted.')->render());
}
