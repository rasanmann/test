<?php
/***************************************************
 * Airports
 ***************************************************/

$query = \Drupal::entityQuery('node')
  ->condition('type', 'airport')
  ->condition('status', 1)
;

$nids = $query->execute();

echo count($nids) . PHP_EOL;

$results = true;
$chunks = 100;
$index = 0;

while ($results) {
// Unpublish all airports
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'airport')
    ->condition('status', 1)
    ->range(0, 100)
  ;

  $nids = $query->execute();

  echo current(array_keys($nids)) . PHP_EOL;

  $results = (count($nids));

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
}

// Find airports that are attached to destinations
$query = \Drupal::entityQuery('node')
  ->condition('type', 'destination')
  ->condition('status', 1)
;

$nids = $query->execute();

$storage_handler = \Drupal::entityTypeManager()->getStorage('node');
$entities = $storage_handler->loadMultiple($nids);

$airports_nids = array();

foreach($entities as $entity) {
  /** @var \Drupal\node\Entity\Node $entity */
  $fields = $entity->getFields();

  /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $airport */
  $airports_nids[] = current(current($entity->get('field_airport')->getValue()));
}

$airports_nids = array_unique($airports_nids);
$airports_nids = array_combine($airports_nids, $airports_nids);

// Publish these nodes
$storage_handler = \Drupal::entityTypeManager()->getStorage('node');
$entities = $storage_handler->loadMultiple($airports_nids);
$total = count($entities);
$progress = 1;

foreach($entities as $entity) {
  /** @var \Drupal\node\Entity\Node $entity */
  echo sprintf('%s/%s', $progress, $total) . PHP_EOL;
  $entity->setPublished(NODE_PUBLISHED);
  $entity->save();
  $progress++;
}