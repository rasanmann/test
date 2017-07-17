<?php
/***************************************************
 * Airlines
 ***************************************************/

// Unpublish all airlines
$query = \Drupal::entityQuery('node')
  ->condition('type', 'airline')
  ->condition('status', 1)
;

$nids = $query->execute();

$storage_handler = \Drupal::entityTypeManager()->getStorage('node');
$entities = $storage_handler->loadMultiple($nids);
$total = count($entities);
$progress = 1;

foreach($entities as $entity) {
  /** @var \Drupal\node\Entity\Node $entity */
  echo sprintf('%s/%s', $progress, $total) . PHP_EOL;
  $entity->setPublished(NODE_NOT_PUBLISHED);
  $entity->save();
  $progress++;
}

// Publish only those with logos
$query = \Drupal::entityQuery('node')
  ->condition('type', 'airline')
  ->condition('field_color_logo', 'NULL', '!=')
;

$nids = $query->execute();

$storage_handler = \Drupal::entityTypeManager()->getStorage('node');
$entities = $storage_handler->loadMultiple($nids);
$total = count($entities);
$progress = 1;

foreach($entities as $key => $entity) {
  echo sprintf('%s/%s', $progress, $total) . PHP_EOL;
  /** @var \Drupal\node\Entity\Node $entity */
  $entity->setPublished(NODE_PUBLISHED);
  $entity->save();
  $progress++;
}