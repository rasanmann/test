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
      $form = BillsPaymentForm::create(\Drupal::getContainer());
      $formState = new FormState();
      $formState->set('form_is_enabled', isset($config['form_is_enabled']) ? 1 : 0);

      $config = \Drupal::config('yqb_bills.settings');

      $showForm = $config->get('yqb_bills.form_is_enabled');

      if($showForm == 0){
          return [];
      }

      $builtForm = \Drupal::formBuilder()->buildForm($form, $formState);
      $build['form'] = $builtForm;


    return $build;
  }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->setConfigurationValue('form_is_enabled', $form_state->getValue('form_is_enabled'));
    }




}
