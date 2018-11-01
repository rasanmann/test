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
 * Provides a 'Hero Search' Block
 *
 * @Block(
 *   id = "hero-search-block",
 *   admin_label = @Translation("Hero search block"),
 * )
 */
class HeroSearchBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\yqb_blocks\Form\HeroSearchBlockForm');
    $weather = Block::load('weatherblock');

    return [
      '#theme' => 'hero-search-block',
      '#form' => $form,
      '#weather' => \Drupal::entityTypeManager()->getViewBuilder('block')->view($weather),
    ];
  }
}
