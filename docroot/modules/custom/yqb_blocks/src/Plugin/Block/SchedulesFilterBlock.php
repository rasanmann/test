<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Schedules Filter Block' Block
 *
 * @Block(
 *   id = "schedules-filter-block",
 *   admin_label = @Translation("Schedules Filter Block"),
 * )
 */
class SchedulesFilterBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_path = \Drupal::service('path.current')->getPath();
    $url_object = \Drupal::service('path.validator')->getUrlIfValid($current_path);
    $route_name = $url_object->getRouteName();

    // Find airlines
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'airline')
        ->condition('status', 1)
    ;

    $nodes = Node::loadMultiple($query->execute());

    $airlines = array_map(function($node) {
      return $node->getTitle();
    }, $nodes);

    // Find airports
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'airport')
        ->condition('status', 1)
    ;

    $nodes = Node::loadMultiple($query->execute());

    $airports = array_map(function($node) {
      return $node->get('field_city')->value;
    }, $nodes);

    return [
      '#theme' => 'schedules-filter-block',
      '#current_view' => (preg_match('/arrivals/', $route_name)) ? 'arrivals' : 'departures',
      '#current_day' => (preg_match('/today/', $route_name)) ? 'today' : 'tomorrow',
      '#last_updated' => date('Y-m-d H:i:s', \Drupal::configFactory()->getEditable('yqb_migrate.cron')->get('last_updated')),
      '#airlines' => $airlines,
      '#airports' => $airports,
      // Interactive block, disable cache
      '#cache' => [
        'max-age' => 0
      ],
    ];
  }
}