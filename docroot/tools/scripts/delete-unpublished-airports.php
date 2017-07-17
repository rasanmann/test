<?php
/***************************************************
 * Airports
 ***************************************************/

$query = \Drupal::entityQuery('node')
  ->condition('type', 'airport')
  ->condition('status', 0)
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
    ->condition('status', 0)
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
    $entity->delete();
    $progress++;
  }
}