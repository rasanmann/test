<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Controller\FlightPlannerController.
 */

namespace Drupal\yqb_wait_times\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the ParkingBooker module.
 */
class WaitTimesController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function index() {
    $wait_config = \Drupal::configFactory()->getEditable('yqb_wait_times.wait');

    $time = round(floatval($wait_config->get('expectedWaitTime')) / 60);

    $element = [
      '#markup' => "Temps d'attente : " . $time . " minutes",
    ];
    
    return $element;
  }

}
?>