<?php

namespace Drupal\yqb_delete_files;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileUsage\FileUsageInterface;

class FilesService {

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var \Drupal\file\FileUsage\FileUsageInterface */
  protected $fileUsage;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileUsageInterface $file_usage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
  }

  public function cleanUp() {
    $age = \Drupal::config('system.file')->get('temporary_maximum_age');
    $file_storage = $this->entityTypeManager->getStorage('file');
    $deleted_files_count = 0;

    if ($age) {
      $fids = \Drupal::entityQuery('file')
        ->condition('changed', REQUEST_TIME - $age, '<')
        ->execute();
      $files = $file_storage->loadMultiple($fids);
      /** @var \Drupal\file\Entity\File $file */
      foreach ($files as $file) {
        $references = $this->fileUsage->listUsage($file);
        if (empty($references)) {
          if ($file->isPermanent()) {
            $file->setTemporary();
            $file->save();
          }
          else {
            $file->delete();
            $deleted_files_count++;
          }
        }
      }
    }

    return $deleted_files_count;
  }

}
