<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'Weather' Block
 *
 * @Block(
 *   id = "weather-menu-block",
 *   admin_label = @Translation("Weather block"),
 * )
 */
class WeatherMenuBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $weather_config = \Drupal::configFactory()->getEditable('yqb_weather.weather');

    return [
      '#theme' => 'weather-menu-block',
      '#cache' => ['max-age' => 5 * 60],
      '#sky' => $weather_config->get('sky'),
      '#forecast' => $weather_config->get('forecast'),
      '#temperature' => round($weather_config->get('temperature')),
    ];
  }
}
