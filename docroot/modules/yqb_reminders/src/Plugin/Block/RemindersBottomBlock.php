<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\yqb_reminders\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\yqb_reminders\Form\RemindersForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yqb_reminders\Form\RemindersFormSelect;

/**
 * Provides a 'Reminders' Block
 *
 * @Block(
 *   id = "reminders_block",
 *   admin_label = @Translation("Reminders block"),
 * )
 */
class RemindersBottomBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['full_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Afficher le formulaire en pleine largeur'),
      '#default_value' => isset($config['full_width']) ? $config['full_width'] : false
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('full_width', $form_state->getValue('full_width'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $useFullWidth = isset($config['full_width']) ? $config['full_width'] : FALSE;

    $userId = (isset($_GET['user_id'])) ? $_GET['user_id'] : ((\Drupal::currentUser()) ? \Drupal::currentUser()->id() : null);

    // Decide which form to display based on user flight and webview
    if(!empty($userId) && isset($_GET['webview'])){
      \Drupal::service('page_cache_kill_switch')->trigger();
      
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'user_flight')
        ->sort('field_flight_date', 'ASC')
        ->sort('field_flight_time', 'ASC')
        ->condition('field_user', $userId)
        ->condition('field_state', 'travel')
        ->condition('field_archived', false);

      $results = $query->execute();

      $render['#title'] = $this->t('Saisissez les informations afin qu\'un proche puisse suivre l\'état de votre vol :');

      if(!empty($results)){
        $form = RemindersFormSelect::create(\Drupal::getContainer());
      }else{
        $form = RemindersForm::create(\Drupal::getContainer());
      }
    }else{
      $form = RemindersForm::create(\Drupal::getContainer());
    }


    $formState = new FormState();
    $formState->set('full_width', isset($config['full_width']) ? $config['full_width'] : false);
    $builtForm = \Drupal::formBuilder()->buildForm($form, $formState);

    $url = \Drupal\Core\Url::fromRoute(sprintf('yqb_reminders.%s.form', \Drupal::languageManager()->getCurrentLanguage()->getId()))->toString();

    if (!empty($_SERVER['QUERY_STRING'])) {
      $url .= '?' . $_SERVER['QUERY_STRING'];
    }

    $render['form'] = $builtForm;
    $render['form']['#action'] = $url;
    $render['form']['#attributes']['class'][] = 'form';
    $render['form']['#attributes']['class'][] = 'form-inverse';

    $render['#attributes']['class'][] = (!$useFullWidth) ? 'col-md-6' : NULL;

    return $render;
  }
}
