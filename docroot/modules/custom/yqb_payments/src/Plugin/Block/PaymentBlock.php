<?php

namespace Drupal\yqb_payments\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "yqb_payments_payment_block",
 *   admin_label = @Translation("Pay Bills"),
 *   category = @Translation("YQB"),
 * )
 */
class PaymentBlock extends BlockBase
{
  public function build()
  {
    if (Drupal::config('yqb_payments.settings')->get('form_is_disabled')) {
      return [];
    }

    return Drupal::formBuilder()->getForm(Drupal\yqb_payments\Form\PaymentForm::class);
  }
}
