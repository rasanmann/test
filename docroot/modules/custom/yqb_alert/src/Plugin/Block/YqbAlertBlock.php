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
        '#title' => $this->t('Alerte française (courte)'),
      '#placeholder' => "Entrer le text de l'alerte ici",
      '#description' => 'Alerte francaise boîte rouge',
      '#default_value'=> isset($config['french_alert']) ? $config['french_alert'] : "",
    ];

    $form['english_alert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('English Alert (short)'),
      '#placeholder' => "Enter the text for the alert",
      '#description' => 'English alert in red box',
      '#default_value'=> isset($config['english_alert']) ? $config['english_alert'] : "",
    ];

    $form['french_alert_full'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Alerte française (Complète)'),
        '#format'=> 'full_html',
        '#placeholder' => "Entrer le text complet de l'alerte ici",
        '#description' => 'Le texte mis dans cette zone sera celui afficher sur la page alerte complete',
        '#default_value'=> isset($config['french_alert_full']['value']) ? $config['french_alert_full']['value'] : "",
    ];

    $form['english_alert_full'] = [
        '#type' => 'text_format',
        '#title' => $this->t('English Alert Full'),
        '#format' => 'full_html',
        '#placeholder' => "Enter the text for the full alert here",
        '#description' => 'The text compose in this zone will be shown on the full page alert',
        '#default_value'=> isset($config['english_alert_full']['value']) ? $config['english_alert_full']['value'] : "",
    ];

    $form['alert_is_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('(IMPORTANT !) Cocher pour afficher l\'alerte'),
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
    $this->configuration['french_alert_full'] = $form_state->getValue('french_alert_full');
    $this->configuration['english_alert_full'] = $form_state->getValue('english_alert_full');
    $this->configuration['alert_is_enable'] = $form_state->getValue('alert_is_enable');


  }
}
