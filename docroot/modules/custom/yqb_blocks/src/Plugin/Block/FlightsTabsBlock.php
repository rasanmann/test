<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Flights Tabs' Block
 *
 * @Block(
 *   id = "flights-tabs-block",
 *   admin_label = @Translation("Flights tabs block"),
 * )
 */
class FlightsTabsBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $arrivals = Block::load('views_block__arrivals_block_1');
    $departures = Block::load('views_block__departures_block_1');
    $parking = Block::load('parkingbookerblock');

    return [
      '#theme' => 'flights-tabs-block',
      '#arrivals' => \Drupal::entityTypeManager()->getViewBuilder('block')->view($arrivals),
      '#departures' => \Drupal::entityTypeManager()->getViewBuilder('block')->view($departures),
      '#parking' => \Drupal::entityTypeManager()->getViewBuilder('block')->view($parking),
    ];
  }
}
