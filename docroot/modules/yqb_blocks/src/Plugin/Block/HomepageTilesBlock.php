<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkTree;
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
	/**
	 * {@inheritdoc}
	 */
	public function build() {
		$language = \Drupal::languageManager()->getCurrentLanguage()->getId();

		$menu = \Drupal::menuTree()->load('homepage-tiles-global', new \Drupal\Core\Menu\MenuTreeParameters());
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
							'hide_content' => (bool)$node->field_tile_hide_content->value,
							'icon_class' => $node->field_tile_icon_list->value,
							'background_color' => $node->field_tile_background_color->value,
					];

					if (isset($node->field_tile_link->first()->uri)) {
						$uri = $node->field_tile_link->first()->uri;
						$tile['url'] = Url::fromUri($uri);
						$tile['target'] = (preg_match('/^internal|^entity|aeroportdequebec\.com/', $uri)) ? '_self' : '_blank';
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
				'#tiles' => $tiles
		];
	}
}