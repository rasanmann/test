<?php

namespace Drupal\yqb_delete_files\Commands;

use Drupal\yqb_delete_files\FilesService;
use Drush\Commands\DrushCommands;

class YqbDeleteFilesCommands extends DrushCommands {

  protected $filesService;

  public function __construct(FilesService $files_service) {
    parent::__construct();
    $this->filesService = $files_service;
  }


  /**
   * @command yqb:delete_files
   * @aliases yqb:delf
   */
  public function delete_files() {
    $deleted_files_count = $this->filesService->cleanUp();
    $this->output()->writeln(\Drupal::translation()->formatPlural($deleted_files_count, '1 file deleted.', '@count files deleted.')->render());
    \Drupal::logger('yqb_delete_files')->notice("File(s) deleted : @deleted_files_count", ['@deleted_files_count' => $deleted_files_count]);
  }

}
