<?php

namespace Drupal\yqb_payments;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Online Payment entities.
 *
 * @ingroup yqb_payments
 */
class YqbPaymentEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\yqb_payments\Entity\YqbPaymentEntity */
    $row['transaction_no'] = Link::createFromRoute(
      $entity->label(),
      'entity.yqb_payment.canonical',
      ['yqb_payment' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
