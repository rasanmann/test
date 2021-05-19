<?php

namespace Drupal\yqb_mailchimp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'yqb_mailchimp_admin_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['yqb_mailchimp.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yqb_mailchimp.settings');

    $mailchimpLists = mailchimp_get_lists();
    $form['list_id'] = [
      '#type' => 'select',
      '#title' => t('List'),
      '#options' => $this->buildOptionList($mailchimpLists),
      '#required' => TRUE,
      '#default_value' => $config->get('list_id') ?? -1,
    ];

    if($config->get('list_id')){
      $list_id = $config->get('list_id');
    }

    if (!empty($form_state->getValue('list_id'))) {
      $list_id = $form_state->getValue('list_id');
    }
   
    $list_segments = [];
    if (isset($list_id)) {
      $list_segments = mailchimp_campaign_get_list_segments($list_id, "saved");
    }
    
    $form['audience_tag'] = [
      '#type' => 'select',
      '#title' => t('Custom segment'),
      '#description' => "Un segment est un regroupement de condition. (Audience tag, group, interests, etc) Ajouter un segment dans mailchimp pour qu'il soit dans cette liste.",
      '#options' => $this->buildOptionList($list_segments, '-- Entire list --'),
      '#required' => FALSE,
      '#default_value' => $config->get('audience_tag') ?? -1,
    ];
    
    
    $form['template_id'] = [
      '#type' => 'select',
      '#title' => t('Template'),
      '#description' => t('Select a Mailchimp user template to use. Due to a limitation in the API, only templates that do not contain repeating sections are available. If empty, the default template will be applied.'),
      '#options' => $this->buildOptionList($this->getMailchimpTemplates(true), '-- Select --'),
      '#default_value' => $config->get('template_id') ?? -1,
      '#required' => TRUE,
    ];

    $form['from_name'] = [
      '#type' => 'textfield',
      '#title' => t('From Name'),
      '#description' => t('the From: name for your campaign message (not an email address)'),
      '#required' => TRUE,
      '#default_value' => $config->get('from_name') ?? 'Aéroport international Jean-Lesage de Québec (YQB)'
    ];

    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('From Email'),
      '#description' => t('the From: email address for your campaign message.'),
      '#required' => TRUE,
      '#default_value' => $config->get('from_email') ?? 'emplois@yqb.ca'
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('yqb_mailchimp.settings');
    $config
      ->set('list_id', $form_state->getValue('list_id'))
      ->set('audience_tag', $form_state->getValue('audience_tag'))
      ->set('template_id', $form_state->getValue('template_id'))
      ->set('from_name', $form_state->getValue('from_name'))
      ->set('from_email', $form_state->getValue('from_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  private function buildOptionList($list, $no_selection_label = '-- Select --', $labels = []) {
    $options = [];
    if ($no_selection_label) {
      $options[''] = $no_selection_label;
    }
    foreach ($list as $index => $item) {
      if (!isset($item->id)) {
        $label = isset($labels[$index]) ? $labels[$index] : $index;
        if (count($item)) {
          $options[$label] = $this->buildOptionList($item, FALSE, $labels);
        }
      }
      else {
        $options[$item->id] = $item->name;
      }
    }

    return $options;
  }

  protected function getMailchimpTemplates($reset = FALSE) {
    $cache = \Drupal::cache('yqb_mailchimp');
    $cached_templates = $cache->get('templates');

    $all_templates = [];

    // Return cached lists.
    if (!$reset && !empty($cached_templates)) {
      $all_templates = $cached_templates->data;
    }
    // Query lists from the Mailchimp API and store in cache.
    else {
      /* @var \Mailchimp\MailchimpTemplates $mc_templates */
      if ($mc_templates = mailchimp_get_api_object('MailchimpTemplates')) {
        $response = $mc_templates->getTemplates(['count' => 500, 'type' => 'user']);

        foreach ($response->templates as $template) {
          $all_templates[$template->id] = $template;
        }
      }

      $cache->set('templates', $all_templates);
    }

    return $all_templates;
  }
}
