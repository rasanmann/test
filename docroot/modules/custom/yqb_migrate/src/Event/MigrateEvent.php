<?php
/**
 * @file
 * Contains \Drupal\migrate\Event\MigrateMapDeleteEvent.
 */

namespace Drupal\yqb_migrate\Event;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\data_exchange_layer\Connector\DataExchangeLayerConnector;

class MigrateEvent implements EventSubscriberInterface {

  protected $airportsStore = ['iata' => [], 'icao' => []];
  protected $airlinesStore = ['iata' => [], 'icao' => []];

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_IMPORT][] = ['onPreImport', 0];
    $events[MigratePlusEvents::PREPARE_ROW][] = ['onPrepareRow', 0];
    $events[MigrateEvents::POST_IMPORT][] = ['onPostImport', 0];
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

    switch($migration->id()) {
      case 'airlines':
        $data = file_get_contents('https://raw.githubusercontent.com/jpatokal/openflights/master/data/airlines.dat');

        if ($data) {
          file_put_contents('public://csv/airlines.csv', $data);
        }
      break;

      case 'airports':
        $data = file_get_contents('https://raw.githubusercontent.com/jpatokal/openflights/master/data/airports.dat');

        if ($data) {
          file_put_contents('public://csv/airports.csv', $data);
        }
      break;

      case 'destinations':

      break;

      case 'arrivals':
      case 'departures':
        if ($this->storeDataExchangeLayerData($migration->id())) {

        }
      break;
    }
  }

  /**
   * React to a new row.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare-row event.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $row = $event->getRow();

    if ($row->hasSourceProperty('flight_number') && ($row->hasSourceProperty('std') || $row->hasSourceProperty('sta'))) {
      $date = ($row->hasSourceProperty('std')) ? $row->getSourceProperty('std') : $row->getSourceProperty('sta');

      $type = ($event->getMigration()->id() === 'arrivals') ? 'Arrivée' : 'Départ';

      $title = sprintf('%s %s le %s', $type, $row->getSourceProperty('flight_number'), date('Y-m-d', strtotime($date)));

      $row->setSourceProperty('title', $title);
    }

    // Early exit, skip
    if ($row->hasSourceProperty('active')) {
      if ($row->getSourceProperty('active') === 'N' || $row->getSourceProperty('active') === false) {
        $row->setSourceProperty('status', 1);
      } else {
        $row->setSourceProperty('status', 1);
      }
    }

    // Date conversions
    $dateConversions = [
      'etd', 'std', 'atd',
      'eta', 'sta', 'ata',
      'carousel_start_time', 'carousel_end_time'
    ];

    foreach($dateConversions as $dateConversion) {
      if ($row->hasSourceProperty($dateConversion)) {
        // Remove milli-seconds, will trigger a truncate error otherwise
        $newDate = preg_replace('/\.(.*)$/', '', $row->getSourceProperty($dateConversion));

        // Convert date to UTC
        $given = new \DateTime($newDate);
        $given->setTimezone(new \DateTimeZone("UTC"));
        $newDate = $given->format("Y-m-d\TH:i:s");

        // Set source property
        $row->setSourceProperty($dateConversion, $newDate);
      }
    }

    $statusConversions = [
      'arrival_status',
      'departure_status',
    ];

    foreach($statusConversions as $statusConversion) {
      if ($row->hasSourceProperty($statusConversion)) {
        $tid = $this->findStatusTID($row->getSourceProperty($statusConversion));

        if ($tid) {
          // Replace
          $row->setSourceProperty($statusConversion, $tid);
        }
      }
    }

    // Airport code conversion
    if ($row->hasSourceProperty('airport_iata')) {
      $airportNID = $this->findAirportNID($row->getSourceProperty('airport_iata'), 'iata');

      if ($airportNID) {
        // Replace current airport code by airport NID
        $row->setSourceProperty('airport_nid', $airportNID);
      } else if ($row->hasSourceProperty('airport_icao')) {
        // Fallback to ICAO
        $airportNID = $this->findAirportNID($row->getSourceProperty('airport_icao'), 'icao');

        if ($airportNID) {
          $row->setSourceProperty('airport_nid', $airportNID);
        }
      }
    }

    // Stops code conversion
    if ($row->hasSourceProperty('stops')) {
      $stopsIata = explode(',', $row->getSourceProperty('stops'));
      $stopsNID = [];

      foreach($stopsIata as $stopIata) {
        $airportNID = $this->findAirportNID($stopIata, 'iata');

        if ($airportNID) {
          $stopsNID[] = $airportNID;
        }
      }

      $row->setSourceProperty('stops', $stopsNID);
    }

    // Airline code conversion
    if ($row->hasSourceProperty('airline_icao')) {
      $airlineNID = $this->findAirlineNID($row->getSourceProperty('airline_icao'), 'icao');

      if ($airlineNID) {
        // Replace current airport code by airport NID
        $row->setSourceProperty('airline_nid', $airlineNID);
      } else if ($row->hasSourceProperty('airline_iata')) {
        var_dump('Fallback ' . $row->getSourceProperty('airline_icao') . ' ' . $row->getSourceProperty('airline_iata'));
        // Fallback to IATA
        $airlineNID = $this->findAirlineNID($row->getSourceProperty('airline_iata'), 'iata');

        if ($airlineNID) {
          $row->setSourceProperty('airline_nid', $airlineNID);
        }
      }
    }
  }

  /**
   * React to the end of a new import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The pre-import event.
   */
  public function onPostImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    $migrationId = $migration->id();

    switch($migrationId) {
      case 'arrivals':
      case 'departures':
        // Delete old data to make sure there are no expired flights visible
        $this->cleanup($migrationId);

        // Query current content
        $query = \Drupal::entityQuery('node')->condition('type', rtrim($migrationId, 's'));

        $ids = $query->execute();

        $nodes = \Drupal\node\Entity\Node::loadMultiple($ids);

        foreach($nodes as $node) {
          /** @var \Drupal\node\Entity\Node $node */
          if (!$node->hasTranslation('en')) {
            $flightNumber = $node->field_flight_number->value;
            $date = date('Y-m-d', strtotime(($migrationId === 'arrivals') ? $node->field_sta->value : $node->field_std->value));
            $type = ($migrationId === 'arrivals') ? 'Arrival' : 'Departure';;

            $title = sprintf('%s %s on %s', $type, $flightNumber, $date);

            $node->addTranslation('en', [
                'title' => $title
            ])->save();
          }
        }
      break;
    }
  }

  /**
   * Store arrivals and departures into CSV files
   * @return bool
   */
  private function storeDataExchangeLayerData($type = 'departures') {
    $skipSync = false;

    $destination = sprintf('public://csv/%s.csv', $type);

    if ($skipSync) {
      $data = json_decode(file_get_contents($destination));
    } else {
      $layer = new DataExchangeLayerConnector();

      switch($type) {
        case 'departures':
          $data = $layer->getDepartures();
          // TODO : fetch file name from actual yml config
          if ($data && $this->storeDepartures($data, $destination)) {

          }
          break;
        case 'arrivals':
          $data = $layer->getArrivals();
          // TODO : fetch file name from actual yml config
          if ($data && $this->storeArrivals($data, $destination)) {

          }
          break;
      }
    }


    return true;
  }

  private function storeDepartures($departures, $file) {
    $data = [];

    $data[] = [
      'id',
      'airline_iata',
      'airline_icao',
      'airline_name',
      'flight_number',
      'gate',
      'is_internal',
      'code_shares',
      'destination_iata',
      'destination_icao',
      'destination_city',
      'destination_airport',
      'departure_status',
      'stops',
      'etd',
      'std',
      'atd',
    ];

    foreach($departures as $departure) {
      // Skip is internal
      if($departure->isInternal) continue;

      $stops = [];

      foreach($departure->destination as $stop) {
        $stops[] = $stop->iataCode;
      }

      $row = [
        $departure->flightUniqueId,
        $departure->airline->iataCode,
        $departure->airline->icaoCode,
        $departure->airline->name,
        $departure->flightNumber,
        $departure->gate,
        $departure->isInternal,
        $departure->codeShares,
        end($departure->destination)->iataCode,
        end($departure->destination)->icaoCode,
        end($departure->destination)->airportCityName,
        end($departure->destination)->airportName,
        $departure->departureStatus,
        implode(',', $stops),
        $departure->etd,
        $departure->std,
        $departure->atd,
      ];

      $data[] = $row;
    }

    return $this->saveCSV($file, $data);
  }

  private function storeArrivals($arrivals, $file) {
    $data = [];

    $data[] = [
      'id',
      'airline_iata',
      'airline_icao',
      'airline_name',
      'flight_number',
      'gate',
      'is_internal',
      'code_shares',
      'origin_iata',
      'origin_icao',
      'origin_city',
      'origin_airport',
      'arrival_status',
      'carousel_start_time',
      'carousel_end_time',
      'carousel_name',
      'stops',
      'eta',
      'sta',
      'ata',
    ];

    foreach($arrivals as $arrival) {
      // Skip is internal
      if($arrival->isInternal) continue;

      $stops = [];

      foreach($arrival->origin as $stop) {
        $stops[] = $stop->iataCode;
      }

      $row = [
        $arrival->flightUniqueId,
        $arrival->airline->iataCode,
        $arrival->airline->icaoCode,
        $arrival->airline->name,
        $arrival->flightNumber,
        $arrival->gate,
        $arrival->isInternal,
        $arrival->codeShares,
        reset($arrival->origin)->iataCode,
        reset($arrival->origin)->icaoCode,
        reset($arrival->origin)->airportCityName,
        reset($arrival->origin)->airportName,
        $arrival->arrivalStatus,
        $arrival->carousel->startTime,
        $arrival->carousel->endTime,
        str_replace(['-A', '-B'], "", $arrival->carousel->name),
        implode(',', $stops),
        $arrival->eta,
        $arrival->sta,
        $arrival->ata,
      ];

      $data[] = $row;
    }

    return $this->saveCSV($file, $data);
  }

  private function cleanup($type = 'departures'){
    $destination = sprintf('public://csv/%s.csv', $type);

    // Newly imported data, build a list of all flight numbers present
    $data = array_map('str_getcsv', file($destination));
    array_walk($data, function(&$a) use ($data) {
      $a = array_combine($data[0], $a);
    });
    array_shift($data);

    $importedFlightIds = [];
    if(is_array($data)) {
      foreach ($data as $item) {
        $importedFlightIds[] = $item['id'];
      }
    }

    // For Drush
    echo sprintf('New imported %s count => %d.', $type, sizeof($importedFlightIds)) . PHP_EOL;

    if(!empty($importedFlightIds)) {
      // Query current content
      $query = \Drupal::entityQuery('node')->condition('type', rtrim($type, 's'));

      $result = $query->execute();

      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $currentData = $node_storage->loadMultiple($result);

      // For Drush
      echo sprintf('Current data count => %d.', sizeof($currentData)) . PHP_EOL;

      // Check if current data exists in newly imported, if not -> delete
      foreach ($currentData as $item) {
        if (!in_array($item->field_unique_id->value, $importedFlightIds)) {
          // For Drush
          echo sprintf('Deleting unmatched %s.', $item->field_unique_id->value) . PHP_EOL;

          $item->delete();
        }
      }
    }
  }

  /**
   * Saves data to a CSV file
   * @param $file
   * @param $data
   * @return bool
   */
  private function saveCSV($file, $data) {
    $fp = fopen($file, 'w+');

    if (!$fp)  {
      return false;
    }

    foreach ($data as $row) {
      fputcsv($fp, $row);
    }

    return fclose($fp);
  }

  /**
   * @param $code
   * @param string $codeType
   * @return bool|mixed
   */
  private function findAirportNID($code, $codeType = 'iata') {
    // Save a trip to DB
    if (array_key_exists($code, $this->airportsStore[$codeType])) {
      return $this->airportsStore[$codeType][$code];
    }

    // Find airports with corresponding code
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'airport')
      ->condition('status', 1)
      ->condition('field_' . $codeType, $code)
    ;

    $nids = $query->execute();

    if (count($nids)) {
      $nid = current($nids);

      $this->airportsStore[$codeType][$code] = $nid;

      return $nid;
    } else  {
      return false;
    }
  }

  /**
   * @param $code
   * @param string $codeType
   * @return bool|mixed
   */
  private function findAirlineNID($code, $codeType = 'iata') {
    // Save a trip to DB
    if (array_key_exists($code, $this->airlinesStore[$codeType])) {
      return $this->airlinesStore[$codeType][$code];
    }

    // Find airline with corresponding code
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'airline')
      ->condition('status', 1)
      ->condition('field_' . $codeType, $code)
    ;

    $nids = $query->execute();

    if (count($nids)) {
      $nid = current($nids);

      $this->airlinesStore[$codeType][$code] = $nid;

      return $nid;
    } else  {
      return false;
    }
  }

  /**
   * @param $code
   * @param string $codeType
   * @return bool|mixed
   */
  private function findStatusTID($status) {
    // Find airline with corresponding code
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'flight_statuses')
      ->condition('name', $status)
    ;

    $tids = $query->execute();

    if (count($tids)) {
      return current($tids);
    } else  {
      $term = Term::create([
        'name' => $status,
        'vid' => 'flight_statuses',
      ])->save();

      if ($term) {
        return $this->findStatusTID($status);
      } else  {
        return false;
      }
    }
  }
}
