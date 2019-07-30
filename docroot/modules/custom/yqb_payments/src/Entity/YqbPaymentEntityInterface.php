<?php

namespace Drupal\yqb_payments\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Online Payment entities.
 *
 * @ingroup yqb_payments
 */
interface YqbPaymentEntityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Online Payment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Online Payment.
   */
  public function getCreatedTime();

  /**
   * Sets the Online Payment creation timestamp.
   *
   * @param int $timestamp
   *   The Online Payment creation timestamp.
   *
   * @return \Drupal\yqb_payments\Entity\YqbPaymentEntityInterface
   *   The called Online Payment entity.
   */
  public function setCreatedTime($timestamp);

}
