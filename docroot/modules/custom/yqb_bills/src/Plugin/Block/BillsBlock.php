<?php

namespace Drupal\yqb_bills\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormState;
use Drupal\yqb_bills\Form\BillsPaymentForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yqb_bills\Form;


/**
 * Provides a 'BillsBlock' block.
 *
 * @Block(
 *  id = "bills_block",
 *  admin_label = @Translation("Bills block"),
 * )
 */
class BillsBlock extends BlockBase implements BlockPluginInterface{

  /**
   * {@inheritdoc}
   */
  public function build() {
    $psStoreId = [
      '#type' => 'hidden',
      '#name' => 'ps_store_id',
      '#value' => 'HEJGN34601'

    ];

    $hppKey = [
      '#type' => 'hidden',
      '#name' => 'hpp_key',
      '#value' => 'hpOUJB91ZLFF'

    ];

    $lang = [
      '#type' => 'hidden',
      '#name' => 'lang',
      '#value' => \Drupal::languageManager()->getCurrentLanguage()->getId() . '-ca'
    ];

    $fields = [
      'bill_last_name' => $this->t("Nom"),
      'bill_first_name' => $this->t("Prénom"),
      'email' => $this->t("Courriel"),
      'bill_company_name' => $this->t("Nom de votre entreprise"),
      'order_id' => $this->t("Numéro de facture"),
      'cust_id' => $this->t("Numéro de client"),
      'charge_total' => $this->t("Montant à débourser (Entrer les décimales Ex.: 20.00)"),
    ];

    $form = [
      '#type' => 'form',
      '#attributes' => [
        'class' => ['col-sm-6'],
        'action' => 'https://www3.moneris.com/HPPDP/index.php',
        'method' => 'post',
        'target' => 'results',
      ],
      'ps_store_id' => $psStoreId,
      'hpp_key' => $hppKey,
      'lang' => $lang,
    ];

    foreach ($fields as $name => $label) {
      $form[$name] = [
        '#type' => 'textfield',
        '#title' => $label,
        '#name' => $name,
        '#required' => true,
      ];
    }
    $form['note'] = [
      '#type' => 'checkbox',
      '#name' => 'note',
      '#title' => $this->t('Je désire recevoir factures et états de compte par courriel.'),
      '#default_value' => $this->t("Factures et états de compte par courriel."),
      '#return_value' => $this->t("Factures et états de compte par courriel."),
    ];

    $form['actions'] = [
      '#type' => 'button',
      '#value' => $this->t("Soumettre"),
    ];


      //$form = BillsPaymentForm::create(\Drupal::getContainer());
      //$formState = new FormState();
      //$formState->set('form_is_enabled', isset($config['form_is_enabled']) ? 1 : 0);

      $config = \Drupal::config('yqb_bills.settings');

      $showForm = $config->get('yqb_bills.form_is_enabled');

      if($showForm == 0){
          return [];
      }

      //$builtForm = \Drupal::formBuilder()->buildForm($form, $formState);
      $build['form'] = $form;


    return $build;
  }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->setConfigurationValue('form_is_enabled', $form_state->getValue('form_is_enabled'));
    }




}
