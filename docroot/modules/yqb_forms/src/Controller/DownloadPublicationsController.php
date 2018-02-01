<?php
/**
 * @file
 * Contains \Drupal\yqb_helpdesk\Controller\SpeakWebhookController.
 */

namespace Drupal\yqb_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;

/**
 * Provides route for publications download requests page.
 */
class DownloadPublicationsController extends ControllerBase {
  
  public function __construct() {
  }

  /**
   * Display all publications requests in a table
   */
  public function index(){
    $table = [
        '#type' => 'table',
        '#header' => ['Fichier', 'Prénom', 'Nom de famille', 'Courriel', 'Compagnie', 'Date'],
        '#rows' => []
    ];
    
    $connection = \Drupal::database();
    $query = $connection->query("SELECT * FROM yqb_download_queries ORDER BY date DESC");
    $results = $query->fetchAll();
    
    foreach($results as $k => $row){
      if(!empty($row->file_id) && is_numeric($row->file_id)) {
        $file = File::load($row->file_id);
        $markup = [];
        $markup['#markup'] = Markup::create("<a href='".file_create_url($file->getFileUri())."' target='_blank'>" . $file->getFilename() . "</a>");
        $file = \Drupal::service('renderer')->render($markup);
      }else{
        $file = '';
      }
      
      $table['#rows'][$k] = [
          $file,
          $row->first_name,
          $row->last_name,
          $row->email,
          $row->company,
          $row->date,
      ];
    }
    
    return $table;
  }
}