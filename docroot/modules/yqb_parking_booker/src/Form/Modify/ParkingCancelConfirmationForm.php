<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form\Modify;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\moneris\Connector\MonerisConnector;
use Drupal\yqb_parking_booker\Form\ParkingFormBase;
use Endroid\QrCode\QrCode;

class ParkingCancelConfirmationForm extends ParkingFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'parking_booker_cancel_confirmation_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Results weren't stored, user probably accessed this page directly, redirect to homepage
        if (!$this->store->get('current_booking')) {
            drupal_set_message($this->t("Aucune réservation correspondant aux informations fournies."), 'error');
            return $this->redirect(sprintf('yqb_parking_booker.%s.modify.index', \Drupal::languageManager()->getCurrentLanguage()->getId()));
        }

        $form = parent::buildForm($form, $form_state);

        unset($form['actions']);

        $form['title'] = [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#attributes' => ['class' => ['title-page']],
            '#value' => \Drupal::service('title_resolver')->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject())
        ];

        $titleConfirmation = [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $this->t("Confirmation de l'annulation de votre réservation."),
        ];

        $notification = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t("Un courriel a été acheminé à @email avec les détails de votre annulation.", ['@email' => $this->store->get('current_booking')->email]),
        ];


        $container = [
            '#type' => 'container',
            '#cache' => ['max-age' => 0],
            '#attributes' => ['class' => ['row']],

            'row' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],

                'col-1' => [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['col-sm-6']],
                    'confirmation' => $titleConfirmation,
                    'notification' => $notification,
                ],

                'col-2' => [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['col-sm-6']],
                ]
            ]
        ];

        $form['container'] = $container;

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

    }
}

?>