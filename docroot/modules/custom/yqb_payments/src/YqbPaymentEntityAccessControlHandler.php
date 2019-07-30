<?php

namespace Drupal\yqb_payments;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Online Payment entity.
 *
 * @see \Drupal\yqb_payments\Entity\YqbPaymentEntity.
 */
class YqbPaymentEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\yqb_payments\Entity\YqbPaymentEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view online payment entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit online payment entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete online payment entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add online payment entities');
  }

}
