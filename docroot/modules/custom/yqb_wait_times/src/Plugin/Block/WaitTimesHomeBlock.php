<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_wait_times\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Wait Times' Block
 *
 * @Block(
 *   id = "wait-times-home-block",
 *   admin_label = @Translation("Wait Times home block"),
 * )
 */
class WaitTimesHomeBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $wait_config = \Drupal::configFactory()->getEditable('yqb_wait_times.wait');

    $time = round(floatval($wait_config->get('expectedWaitTime')) / 60);

    return [
      '#theme' => 'wait-times-home-block',
      '#cache' => ['max-age' => 5 * 60],
      '#time' => $time,
    ];
  }
}
