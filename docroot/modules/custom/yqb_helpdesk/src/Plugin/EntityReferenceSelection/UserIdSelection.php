<?php

namespace Drupal\yqb_helpdesk\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\comment\CommentInterface;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides specific access control for the comment entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:userid",
 *   label = @Translation("User ID selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserIdSelection extends UserSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery(NULL, $match_operator);
    $handler_settings = $this->configuration['handler_settings'];

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition(
        $query->orConditionGroup()
          ->condition('uid', intval($match), $match_operator)
          ->condition('name', $match, $match_operator)
      );
    }

    return $query;
  }
}
