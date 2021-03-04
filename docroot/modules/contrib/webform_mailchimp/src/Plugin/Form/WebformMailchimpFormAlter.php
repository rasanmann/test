<?php

namespace Drupal\webform_mailchimp\Plugin\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_mailchimp\Ajax\WebformMailchimpFetchGroupsCommand;


class WebformMailchimpFormAlter {

  /*
   * Initiates Ajax command to fetch groups from Mailchimp and update the
   * Webform predefined options edit form.
   */
  public static function fetchGroupsAjaxCallback(&$form, FormStateInterface $form_state) {
    $options_id = $form['id']['#default_value'];

    if ($options_id) {
      $response = new AjaxResponse();
      $response->addCommand(new WebformMailchimpFetchGroupsCommand($options_id));
    }
    else {
      \Drupal::messenger()
        ->addMessage("Failed to find Mailchimp information for these predefined options.");
    }

    return $response;
  }

}
