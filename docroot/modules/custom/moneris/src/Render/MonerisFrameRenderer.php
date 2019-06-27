<?php

namespace Drupal\moneris\Render;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;

/**
 * Class MonerisFrameRenderer
 * @package Drupal\moneris\Render
 */
class MonerisFrameRenderer {

  private $defaultParams = [
    'enable_exp' => '1',
    'enable_cvd' => '1',
    'display_labels' => '1',
    'pan_label' => 'Card Number',
    'exp_label' => 'Expiry Date',
    'cvd_label' => 'CVD',
    'pmmsg' => 'true'
  ];

  private $defaultAttributes = [
    'src' => '',
    'frameborder' => '0',
    'width' => '100%',
    'height' => '300px',
    'class' => ['moneris-frame']
  ];

  private $defaultStylesheet = [
    'css_body' => [
      'background' => 'transparent',
    ],
    'css_textbox' => [
      'border-color' => '#999999',
      'border-width' => '2px',
      'border-style' => 'solid',
      'border-radius' => '4px',
      'font-size' => '16px',
      'height' => '60px',
      'margin-bottom' => '20px',
      'padding' => '6px 12px',
    ],
    'css_input_label' => [
      'color' => '#999999',
      'display' => 'block',
      'font-size' => '11px',
      'font-weight' => 'bold',
      'font-family' => '"nimbus-sans", Helvetica, Arial, sans-serif',
      'margin-bottom' => '5px',
      'padding-left' => '3px',
    ],
    'css_textbox_pan' => [
      'width' => '100%',
    ],
    'css_textbox_exp' => [
      'width' => '80px',
    ],
    'css_textbox_cvd' => [
      'width' => '80px',
    ],
  ];

  protected $params = [];
  protected $attributes = [];
  protected $stylesheet = [];

  public function __construct($params = [], $attributes = [], $stylesheet = []) {
    $config = \Drupal::config('moneris.settings');

    $this->params = array_merge($this->defaultParams, $params);
    $this->attributes = array_merge($this->defaultAttributes, $attributes);

    $this->stylesheet = $this->prepareStylesheet(array_merge($this->defaultStylesheet, $stylesheet));

    // Moneris profile ID -- Pick the one that fits the domain
    if($profileConfig = Yaml::decode($config->get('moneris.profile_id'))){
      $prefix = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)) ? "https" : "http";
      $currentDomain = $prefix . '://' . $_SERVER['HTTP_HOST'];

      $profileId = (isset($profileConfig[$currentDomain])) ? $profileConfig[$currentDomain] : $config->get('moneris.profile_id');
    }else{
      $profileId = $config->get('moneris.profile_id');
    }

    $this->params['id'] = $profileId;
    $this->attributes['id'] = Html::getUniqueId('moneris-frame-' . $profileId);

    // Form SRC
    $this->attributes['src'] = $config->get('moneris.api_url') . '/HPPtoken/index.php?' . http_build_query(array_merge($this->params, $this->stylesheet));
  }

  private function prepareStylesheet($stylesheet) {
    // Implode all the things
    return array_map(function($directives) {
      array_walk($directives, function(&$item, $key) {
        $item = $key . ':' . $item;
      });

      return implode(';', $directives);
    }, $stylesheet);
  }

  public function getId()
  {
    return isset($this->attributes['id']) ? $this->attributes['id'] : null;
  }

  public function getRenderArray() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => $this->attributes
    ];
  }
}
