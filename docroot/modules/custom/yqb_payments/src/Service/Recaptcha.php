<?php

namespace Drupal\yqb_payments\Service;

use Drupal;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Exception;

class Recaptcha
{
  protected $config;

  public function __construct(ConfigFactory $configFactory)
  {
    $this->config = $configFactory->get('yqb_payments.settings');
  }

  public function attach(array &$form)
  {
    $recaptcha_src = 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit';
    $form['recaptcha'] = [
      '#markup' => '<div id="recaptcha_element"></div>'
    ];
    $form['#attached'] = [
      'library' => ['yqb_payments/recaptcha'],
      'drupalSettings' => [
        'yqb_payments' => [
          'recaptcha' => [
            'sitekey' => $this->config->get('recaptcha_sitekey')
          ]
        ]
      ],
      'html_head' => [
        [
          [
            '#tag' => 'script',
            '#attributes' => [
              'src' => Url::fromUri($recaptcha_src, ['query' => ['hl' => \Drupal::service('language_manager')->getCurrentLanguage()->getId()], 'absolute' => TRUE])->toString(),
              'async' => TRUE,
              'defer' => TRUE,
            ],
          ],
          'recaptcha_api',
        ],
      ],
    ];
  }

  public function validate(FormStateInterface $formState)
  {
    $valid = false;
    $recaptchaResponse = Drupal::request()->get('g-recaptcha-response');

    if (!empty($recaptchaResponse)) {
      try {
        $response = Drupal::httpClient()->post('https://www.google.com/recaptcha/api/siteverify', [
          'timeout' => 10,
          'form_params' => [
            'secret' => $this->config->get('recaptcha_secret'),
            'response' => $recaptchaResponse,
            'remoteip' => Drupal::request()->getClientIp()
          ]
        ]);
        $jsonObject = \GuzzleHttp\json_decode($response->getBody()->getContents());
        $valid = $jsonObject->success;
      } catch (Exception $e) {
        $valid = false;
      }
    }

    return $valid;
  }
}
