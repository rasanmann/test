<?php
/**
 * @file
 * Contains \Drupal\migrate\Event\MigrateMapDeleteEvent.
 */

namespace Drupal\yqb_app_migrate\Event;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MigrateEvent implements EventSubscriberInterface {

  private $translations = [];
  private $languages = ['fr', 'en'];

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_IMPORT][] = ['onPreImport', 0];
    $events[MigratePlusEvents::PREPARE_ROW][] = ['onPrepareRow', 0];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onPostRowSave', 0];
    return $events;
  }

  /**
   * React to the start of a new import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The pre-import event.
   */
  public function onPreImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    // Get Migration csv
    $file = str_replace('fr', 'en', $migration->getSourceConfiguration()['path']);
    $csv = array_map('str_getcsv', file($file));

    array_walk($csv, function (&$a) use ($csv) {
      $a = array_combine($csv[0], $a);
    });

    // Remove header
    array_shift($csv);

    $this->translations = $csv;
  }

  /**
   * React to a new row.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare-row event.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $row = $event->getRow();

    if ($row->hasSourceProperty('field_zone')) {
      $zone = $this->findZoneNID($row->getSourceProperty('field_zone'));

      if ($zone) {
        $row->setSourceProperty('field_zone', $zone);
      }
    }
  }

  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration();
    $row = $event->getRow();

    // Get NIDs
    $sourceNid = $row->getSourceProperty('nid');
    $destinationNid = current($event->getDestinationIdValues());

    // Loop through translations
    foreach ($this->translations as $translation) {
      if ($translation['nid'] === $sourceNid) {
        if (array_key_exists('body', $translation)) {
          $translation['body'] = [
            'format' => 'full_html',
            'value' => $translation['body']
          ];
        }

        if (array_key_exists('field_zone', $translation)) {
          $zone = $this->findZoneNID($translation['field_zone']);

          if ($zone) {
            $translation['field_zone'] = $zone;
          }
        }

        // Add translation
        $node = Node::load($destinationNid);
        $node->addTranslation('en', $translation);
        $node->save();

        break;
      }
    }

    switch ($migration->id()) {

    }
  }

  /**
   * @param $zone
   * @return bool|mixed
   */
  private function findZoneNID($zone) {
    // Find airports with corresponding code
    $query = \Drupal::entityQuery('node')
                    ->condition('type', 'zone')
                    ->condition('status', 1)
                    ->condition('title', $zone);

    $nids = $query->execute();

    if (count($nids)) {
      $nid = current($nids);

      return $nid;
    } else {
      return false;
    }
  }
}
