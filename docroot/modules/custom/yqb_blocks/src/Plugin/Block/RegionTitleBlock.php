<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Region title' Block
 *
 * @Block(
 *   id = "region-title-block",
 *   admin_label = @Translation("Region title block"),
 * )
 */
class RegionTitleBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'region-title-block'
    ];
  }
}