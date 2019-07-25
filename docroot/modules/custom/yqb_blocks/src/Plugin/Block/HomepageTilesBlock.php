<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Homepage Tiles Block' Block
 *
 * @Block(
 *   id = "homepage-tiles-block",
 *   admin_label = @Translation("Homepage Tiles Block"),
 * )
 */
class HomepageTilesBlock extends BlockBase {

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Tile'),
        $this->t('Remove'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No tile found.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    for ($i = 0; $i < 5; $i++) {
      $weight = $i + 1;

      $form['table-row'][$i]['#attributes']['class'][] = 'draggable';
      $form['table-row'][$i]['#weight'] = $weight;
      $form['table-row'][$i]['tile'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => array(
          'target_bundles' => array('homepage_tiles'),
        ),
        '#required' => TRUE
      ];
      $form['table-row'][$i]['remove'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove'),
        '#ajax' => [
          'callback' => [$this, 'removeRow']
        ]
      ];
      $form['table-row'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => 'Weight for this line',
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => [
          'class' => [
            'table-sort-weight',
          ],
        ],
      ];
    }

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    ksm($form_state->getValues());
  }

  public function removeRow(&$form, FormStateInterface $form_state) {
    ksm($form_state);
    return $form['table-row'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $menu = \Drupal::menuTree()
                   ->load('homepage-tiles-global',
                     new \Drupal\Core\Menu\MenuTreeParameters());
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $menu = \Drupal::menuTree()->transform($menu, $manipulators);

    $ids = [];
    foreach ($menu as $key => $item) {
      $url = $item->link->getUrlObject();
      $path = $url->getInternalPath();

      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $ids[] = $matches[1];
      }
    }

    $tiles = [];
    if (!empty($ids)) {
      $nodes = Node::loadMultiple($ids);

      foreach ($nodes as $node) {
        if ($node->hasTranslation($language)) {
          $node = $node->getTranslation($language);

          $tile = [
            'title' => $node->title->value,
            'description' => $node->field_tile_body->value,
            'hide_content' => (bool) $node->field_tile_hide_content->value,
            'icon_class' => $node->field_tile_icon_list->value,
            'background_color' => $node->field_tile_background_color->value,
            'format' => $node->field_format->value,
            'trigger_nav' => $node->field_trigger_nav->value,
          ];

          if (isset($node->field_tile_link->first()->uri)) {
            $uri = $node->field_tile_link->first()->uri;
            $tile['url'] = Url::fromUri($uri);
            $tile['target'] = (preg_match('/^internal|^entity|aeroportdequebec\.com/',
              $uri)) ? '_self' : '_blank';
          }

          if (isset($node->field_tile_link->first()->uri)) {
            $tile['cta'] = $node->field_tile_link->first()->title;
          }

          if (isset($node->get('field_tile_background_image')->entity)) {
            $tile['background_image'] = file_create_url($node->get('field_tile_background_image')->entity->getFileUri());
          }

          if (isset($node->get('field_tile_icon')->entity)) {
            $tile['icon'] = file_create_url($node->get('field_tile_icon')->entity->getFileUri());
          }

          $tiles[] = $tile;
        }
      }
    }

    return [
      '#theme' => 'homepage-tiles-block',
      '#tiles' => $tiles,
    ];
  }
}
