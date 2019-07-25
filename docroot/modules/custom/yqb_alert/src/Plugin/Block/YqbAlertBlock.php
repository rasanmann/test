<?php

namespace Drupal\yqb_alert\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "yqb_alert_block",
 *   admin_label = @Translation("Yqb Block Alert"),
 * )
 */
class YqbAlertBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $content = null;

    $language == 'fr' ? $content = $this->configuration['french_alert'] : $content = $this->configuration['english_alert'];

    if(!$this->configuration['alert_is_enable']){
      return;
    }

    return [
      '#type'=> 'container',
      '#markup' => $content,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['french_alert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('French Alert'),
      '#placeholder' => "Entrer le text de l'alerte ici",
      '#description' => 'description',
      '#default_value'=> isset($config['french_alert']) ? $config['french_alert'] : "",
    ];

    $form['english_alert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('English Alert'),
      '#placeholder' => "Enter the text for the alert",
      '#description' => 'description',
      '#default_value'=> isset($config['english_alert']) ? $config['english_alert'] : "",
    ];

    $form['alert_is_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cocher pour afficher l\'alerte'),
      '#default_value' => isset($config['alert_is_enable']) ? $config['alert_is_enable'] : false,
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['french_alert'] = $form_state->getValue('french_alert');
    $this->configuration['english_alert'] = $form_state->getValue('english_alert');
    $this->configuration['alert_is_enable'] = $form_state->getValue('alert_is_enable');

  }
}
