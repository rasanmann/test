<?php

namespace Drupal\yqb_destinations;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\data_exchange_layer\Connector\DataExchangeLayerConnector;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\node\Entity\Node;

class Synchronize {

  /** @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object $entityStorage */
  protected $entityStorage;

  /** @var \Drupal\data_exchange_layer\Connector\DataExchangeLayerConnector $delConnector */
  protected $delConnector;

  /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
  protected $logger;

  public function __construct(
    EntityTypeManager $entityTypeManager,
    DataExchangeLayerConnector $delConnector,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    $this->entityStorage = $entityTypeManager->getStorage('node');
    $this->delConnector = $delConnector;
    $this->logger = $loggerFactory->get('yqb_destinations');
  }

  public function sync() {
    $updatedDestinations = $this->getUpdatedDestinations(
      $this->getDirectDepartures('+180 days')
    );

    foreach ($updatedDestinations as $destinationId => $airlines) {
      $destination = Node::load($destinationId);

      $storage = \Drupal::entityTypeManager()->getStorage('field_collection_item');
      $schedules = [];
      foreach ($destination->get('field_destination_schedule') as $item) {
        $schedules[] = $item->value;
      }
      if (!empty($schedules)) {
        $items = $storage->loadMultiple($schedules);
        $storage->delete($items);
        $destination->set('field_destination_schedule', []);
      }

      foreach ($airlines as $airlineId => $dates) {
        $dates = array_unique($dates);
        sort($dates);

        $firstDate = new DrupalDateTime($dates[0]);
        $lastDate = new DrupalDateTime($dates[(count($dates) - 1)]);
        $totalDays = intval($firstDate->diff($lastDate)->format('%a'));

        $everyday = FALSE;
        $totalDates = count($dates);
        if ($totalDates > 100) {
          if ($totalDates == $totalDays || $totalDates == ($totalDays - 1) || $totalDates == ($totalDays + 1)) {
            $everyday = TRUE;
          }
        }

        $newSchedule = FieldCollectionItem::create([
          'field_everyday' => $everyday,
          'field_schedule' => $dates,
          'field_airline' => $airlineId,
          'field_name' => 'field_destination_schedule',
        ]);
        $newSchedule->setHostEntity($destination);
        $newSchedule->save(TRUE);
      }
      $destination->save();
    }
  }

  protected function getUpdatedDestinations($directDepartures) {
    $updatedDestinations = [];

    foreach ($directDepartures as $departure) {
      $airportId = $this->getAirportId($departure->destination[0]->iataCode);
      if ($airportId) {
        $destinationId = $this->getDestinationId($airportId);
        $airlineId = $this->getAirlineId($departure->airline->iataCode);
        if ($destinationId && $airlineId) {
          if (!isset($updatedDestinations[$destinationId])) {
            $updatedDestinations[$destinationId] = [];
          }
          if (!isset($updatedDestinations[$destinationId][$airlineId])) {
            $updatedDestinations[$destinationId][$airlineId] = [];
          }
          $updatedDestinations[$destinationId][$airlineId][] = date('Y-m-d', strtotime($departure->std));
        }
      }
    }

    return $updatedDestinations;
  }

  protected function getDirectDepartures($diff) {
    $directDepartures = [];
    $departures = $this->delConnector->getDepartures(date('Y-m-d'), date('Y-m-d', strtotime($diff)));

    if (!empty($departures)) {
      $directDepartures = array_filter($departures, function ($departure) {
        return count($departure->destination) == 1; // Direct flight.
      });
    }

    return $directDepartures;
  }

  protected function getAirportId($iata) {
    $airportId = FALSE;
    $results = $this->entityStorage
      ->getQuery()
      ->condition('type', 'airport')
      ->condition('field_iata', $iata)
      ->condition('status', Node::PUBLISHED)
      ->execute();

    if (!empty($results) && count($results) == 1) {
      $airportId = reset($results);
    }

    return $airportId;
  }

  protected function getDestinationId($airportId) {
    $destinationId = FALSE;
    $results = $this->entityStorage
      ->getQuery()
      ->condition('type', 'destination')
      ->condition('field_airport', $airportId)
      ->condition('status', Node::PUBLISHED)
      ->execute();

    if (!empty($results) && count($results) == 1) {
      $destinationId = reset($results);
    }

    return $destinationId;
  }

  protected function getAirlineId($iata) {
    $airlineId = FALSE;
    $results = $this->entityStorage
      ->getQuery()
      ->condition('type', 'airline')
      ->condition('field_iata', $iata)
      ->condition('status', Node::PUBLISHED)
      ->execute();

    if (!empty($results) && count($results) == 1) {
      $airlineId = reset($results);
    }

    return $airlineId;
  }
}
