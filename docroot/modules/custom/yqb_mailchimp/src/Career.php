<?php

namespace Drupal\yqb_mailchimp;

use Drupal\Core\Config\Config;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Career implements ContainerInjectionInterface {

  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function sendCareerAlert(Node $node) {
    $alert = new Alert($node);
    $alert->send();
  }

  public function findAvailableCareerAlerts() {
    $nodes = [];

    $query = \Drupal::entityQuery('node');
    $query->condition('status', Node::PUBLISHED);
    $query->condition('type', 'career');
    $query->condition('langcode', 'fr');
    $query->condition('field_start_date', date('Y-m-d', \Drupal::time()->getCurrentTime()), '<=');
    $query->condition('field_end_date', date('Y-m-d', \Drupal::time()->getCurrentTime()), '>=');
    $results = $query->execute();

    if (!empty($results)) {
      $nodes = Node::loadMultiple($results);
    }

    return $nodes;
  }

}
