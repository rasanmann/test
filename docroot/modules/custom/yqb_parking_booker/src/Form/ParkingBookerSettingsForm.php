<?php

/**
 * @file
 * Contains \Drupal\yqb_bills\Form\ParkingBookerSettingsForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class ParkingBookerSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}.
     */
    public function getFormId() {
        return 'parking_booker_settings_form';
    }
    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Form constructor
        $form = parent::buildForm($form, $form_state);
        // Default settings
        $config = $this->config('yqb_parking_booker.settings');

        // Page title field
        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Libellé du bouton:'),
            '#default_value' => $config->get('submit_button'),
            '#description' => $this->t('Entrez le texte qui apparaîtra sur le bouton submit'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}.
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('yqb_parking_booker.settings');

        $config->set('submit_button', $form_state->getValue('label'));

        $config->save();

        return parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getEditableConfigNames() {
        return [
            'yqb_parking_booker.settings',
        ];
    }
}