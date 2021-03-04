<?php

namespace Drupal\webform_mailchimp\Form;

use \Drupal\Core\Form\FormBase;
use \Drupal\Core\Form\FormStateInterface;


class WebformMailchimpImportGroupsOptionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_mailchimp_import_groups_options_form';
  }

  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import_all']['help'] = [
      '#type' => 'item',
      '#title' => t( 'Import All Mailchimp Groups as Predefined Options'),
      '#markup' =>
        t('Adds new predefined options if none exist for groups, and will also update existing predefined options to match those groups in Mailchimp. This will overwrite any local changes you have made to those options.'),
    ];

    $form['import_all']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update all options'),
      '#submit' => ['::importAllGroupsOptions'],
      '#prefix' => '<div data-drupal-selector="import-actions-all" class="form-actions form-wrapper" id="import-actions-all">',
      '#suffix' => '</div>',
    ];

    $form['import_new']['help'] = [
      '#type' => 'item',
      '#title' => t( 'Import Only New Mailchimp Groups as Predefined Options'),
      '#markup' =>
        t('Imports new groups, but will not update existing predefined options. Edit each existing predefined option for full control over what is added.'),
    ];

    $form['import_new']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Import new options'),
      '#submit' => ['::importNewGroupsOptions'],
      '#prefix' => '<div data-drupal-selector="import-actions-new" class="form-actions form-wrapper" id="import-actions-new">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Imports new groups as predefined options and updates existing predefined
   * options.
   */
  public function importAllGroupsOptions(array &$form, FormStateInterface $form_state) {
    _webform_mailchimp_create_interest_groups_predefined_options();
  }

  /*
   * Imports new groups as predefined options, but will not update existing
   * predefined options.
   */
  public function importNewGroupsOptions(array &$form, FormStateInterface $form_state) {
    _webform_mailchimp_create_interest_groups_predefined_options(FALSE);
  }

}
