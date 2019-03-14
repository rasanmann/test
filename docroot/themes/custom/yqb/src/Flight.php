<?php

namespace Drupal\yqb;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;

class Flight implements ContainerInjectionInterface {

  /** @var \Drupal\Core\Datetime\DateFormatter */
  protected $dateFormatter;

  public function __construct(DateFormatter $dateFormatter) {
    $this->dateFormatter = $dateFormatter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  public function delayedTomorrow(EntityInterface $node, $skipStatus = FALSE) {
    $delayedTomorrow = FALSE;

    if ($node instanceof Node && in_array($node->bundle(), ['departure', 'arrival'])) {
      $scheduled = $this->getScheduled($node);
      $estimated = $this->getEstimated($node);

      $scheduledTimestamp = $scheduled->date->getTimestamp();
      $estimatedTimestamp = $estimated->date->getTimestamp();
      $statusTid = $node->get('field_status')->entity->id();

      if (
        $skipStatus &&
        $estimatedTimestamp > $scheduledTimestamp &&
        $estimatedTimestamp >= strtotime('+1 day 00:00:00', $scheduledTimestamp)
      ) {
        $delayedTomorrow = TRUE;
      }
      elseif (
        $statusTid == 11 &&
        $estimatedTimestamp > $scheduledTimestamp &&
        $estimatedTimestamp >= strtotime('+1 day 00:00:00', $scheduledTimestamp)
      ) {
        $delayedTomorrow = TRUE;
      }
    }

    return $delayedTomorrow;
  }

  public function getScheduled(EntityInterface $node) {
    if ($node->bundle() == 'departure') {
      $scheduled = $node->get('field_std')->first();
    }
    else {
      $scheduled = $node->get('field_sta')->first();
    }

    return $scheduled;
  }

  public function getEstimated(EntityInterface $node) {
    if ($node->bundle() == 'departure') {
      $estimated = $node->get('field_etd')->first();
    }
    else {
      $estimated = $node->get('field_eta')->first();
    }

    return $estimated;
  }

  public function getDelayedOutput($timestamp) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $format = 'Y-m-d';
    if($language == 'fr') {
      $format = 'd-m-Y';
    }

    return [
      [
        '#theme' => 'time',
        '#timestamp' => $timestamp,
        '#text' => $this->dateFormatter->format($timestamp, 'custom', 'H:i'),
      ],
      [
        '#markup' => ' <span class="late-24">(' . date($format, $timestamp) . ')</span>',
      ],
    ];
  }
}
