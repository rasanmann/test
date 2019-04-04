<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_parking_booker\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yqb_parking_booker\Form\ParkingSearchForm;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormState;

/**
 * Provides a 'ParkingBooker' Block
 *
 * @Block(
 *   id = "parking_booker_block",
 *   admin_label = @Translation("Parking booker block"),
 * )
 */
class ParkingBookerBottomBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['coupon_input'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Afficher le champ de code promotionnel'),
      '#default_value' => isset($config['coupon_input']) ? $config['coupon_input'] : FALSE,
    ];

    $form['warning'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Afficher l'avertissement de début de réservation"),
      '#default_value' => isset($config['warning']) ? $config['warning'] : FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('coupon_input', $form_state->getValue('coupon_input'));
    $this->setConfigurationValue('warning', $form_state->getValue('warning'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $yqbConfig = \Drupal::config('yqb_parking_booker.settings');
    if ($yqbConfig->get('disabled')) {
      return [
        '#markup' => '<div class="disabled-parking-booker-form">' . check_markup($yqbConfig->get('disabled_text')) . '</div>',
      ];
    }
    else {
      $config = $this->getConfiguration();

      $form = ParkingSearchForm::create(\Drupal::getContainer());
      $formState = new FormState();
      $formState->set('coupon_input', isset($config['coupon_input']) ? $config['coupon_input'] : FALSE);
      $formState->set('warning', isset($config['warning']) ? $config['warning'] : FALSE);
      $builtForm = \Drupal::formBuilder()->buildForm($form, $formState);

      $url = \Drupal\Core\Url::fromRoute(sprintf('yqb_parking_booker.%s.index', \Drupal::languageManager()->getCurrentLanguage()->getId()))->toString();

      if (!empty($_SERVER['QUERY_STRING'])) {
        $url .= '?' . $_SERVER['QUERY_STRING'];
      }

      $render['form'] = $builtForm;
      $render['form']['#action'] = $url;
      $render['form']['#attributes']['class'][] = 'form';
      $render['form']['#attributes']['class'][] = 'form-inverse';

      $render['#attributes']['class'][] = (!$formState->get('warning')) ? 'col-md-6' : NULL;

      return $render;
    }
  }
}
