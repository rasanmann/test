<?php

/**
 * @file
 * Contains \Drupal\advam\Form\AdvamForm.
 */

namespace Drupal\yqb_helpdesk\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\search\SearchPageRepositoryInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

class HelpdeskForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'yqb_helpdesk_helpdesk_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = '0') {
//    $form['#attributes']['data-drupal-form-fields'] = 'edit-submit,edit-keys';

    // https://www.drupal.org/node/2418529
    $form['user'] = [
      '#ajax' => ['progress' => ['type'=> 'custom']],
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_handler' => 'default:userid',
      '#selection_settings' => ['include_anonymous' => false],
      '#attributes' => [
        'placeholder' => $this->t("Type a name or user ID..."),
        'autofocus' => true,
        'autocomplete' => 'off',
        'class'=> ['auto-submit']
      ]
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => ['class'=> ['hidden']],
      // Prevent op from showing up in the query string.
      '#name' => '',
    ];

    if ($user) {
      // Prepare template
      $twig = \Drupal::getContainer()->get('twig');
      $path = drupal_get_path('module', 'yqb_helpdesk') . '/templates/helpdesk-dashboard.html.twig';
      $template = $twig->loadTemplate($path);
      $userData = $this->getUserData($user);

      $form['result'] = [
        '#weight' => 10,
        '#markup' => $template->render(['userData' => $userData])
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
    return $form_state->setRedirect('yqb_helpdesk.helpdesk', [
      'user' => $form_state->getValue('user')
    ]);
  }

  private function getUserData($id) {
    $user = User::load($id);

    if (!empty($user->field_uuid->value)) {
      $user->type = 'Anonyme';
    }
    elseif (!empty($user->field_facebook_user_id->value)) {
      $user->type = 'Facebook';
    }
    elseif (!empty($user->field_google_user_id->value)) {
      $user->type = 'Google';
    }
    else {
      $user->type = 'Régulier';
    }

    if (empty($user->field_push_ios_token->value) && empty($user->field_push_ios_token->value)) {
      $user->notification = 'Aucune';
    }
    else {
      $notifications = [];

      if (!empty($user->field_push_ios_token->value)) {
        $notifications[] = 'iOS';
      }

      if (!empty($user->field_push_android_token->value)) {
        $notifications[] = 'Android';
      }

      $user->notification = implode(' | ', $notifications);
    }

    $userData = [
      'user' => $user,
    ];

    if (!empty($user)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'user_flight')
        ->sort('field_flight_date', 'ASC')
        ->sort('field_flight_time', 'ASC')
        ->condition('field_user', $user->id())
        ->condition('field_archived', FALSE);

      $results = $query->execute();

      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $flights = $node_storage->loadMultiple($results);

      foreach ($flights as &$flight) {
        /**
         * Fetch airline
         */
        $airlineNode = $flight->field_airline->first()
          ->get('entity')
          ->getTarget()
          ->getValue();
        $flight->airline = $airlineNode->title->value;

        /**
         * Fetch Destination Airport
         */
        if ($flight->field_destination_airport->first()->target_id) {
          $destinationNode = $flight->field_destination_airport->first()
            ->get('entity')
            ->getTarget()
            ->getValue();

          $flight->destination = $destinationNode->field_city->value;
        }
        else {
          $flight->destination = 'Québec';
        }

        /**
         * Fetch Origin Aiport
         */
        if ($flight->field_origin_airport->first()->target_id) {
          $originNode = $flight->field_origin_airport->first()
            ->get('entity')
            ->getTarget()
            ->getValue();

          $flight->origin = $originNode->field_city->value;
        }
        else {
          $flight->origin = 'Québec';
        }

        /**
         * Fetch status
         */
        $statusNode = $flight->field_status->first()
          ->get('entity')
          ->getTarget()
          ->getValue();
        $statusNode = \Drupal::service('entity.repository')
          ->getTranslationFromContext($statusNode, $this->language);
        $flight->status = $statusNode->name->value;

        $flight->gate = $flight->field_gate->value;
        $flight->carousel = $flight->field_carousel_name->value;
      }

      $userData['flights'] = $flights;
    }

    return $userData;
  }
}
