<?php

namespace Drupal\layout_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'HumphrayBlock' block.
 *
 * @Block(
 *  id = "humphray_block",
 *  admin_label = @Translation("Humphray block"),
 * )
 */
class HumphrayBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['humphray_block']['#markup'] = 'Implement HumphrayBlock.';

    return $build;
  }

}
