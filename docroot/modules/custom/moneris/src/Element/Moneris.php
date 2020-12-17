<?php

namespace Drupal\moneris\Element;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\moneris\Render\MonerisFrameRenderer;

/**
 * @FormElement("moneris")
 */
class Moneris extends FormElement
{
  public function getInfo()
  {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMoneris']
      ],
      '#theme' => 'input__hidden',
      '#theme_wrappers' => ['form_element']
    ];
  }

  public static function preRenderMoneris($element)
  {
    $configName = isset($element['#moneris_config']) ? $element['#moneris_config'] : 'moneris.settings';
    $config = Drupal::config($configName);
    $elementId = Html::getUniqueId('moneris-token');
    $monerisFrameRenderer = new MonerisFrameRenderer([], [], [], $configName);

    $element['#children']['iframe'] = $monerisFrameRenderer->getRenderArray();
    $element['#id'] = $elementId;

    $element['#attributes']['id'] = $elementId;
    $element['#attributes']['data-frame-id'] = $monerisFrameRenderer->getId();
    $element['#attributes']['type'] = 'hidden';
    Element::setAttributes($element, ['name', 'value']);

    $element['#attached'] = [
      'library' => ['moneris/moneris'],
      'drupalSettings' => [
        'moneris' => [
          'endpoint' => trim($config->get('moneris.api_url'), '/')
        ]
      ]
    ];
    // $element['#attributes']['value'] = $config->get('moneris.api_key');

    return $element;
  }
}
