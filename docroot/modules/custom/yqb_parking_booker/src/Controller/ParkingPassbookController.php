<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Controller\FlightPlannerController.
 */

namespace Drupal\yqb_parking_booker\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\advam\Connector\AdvamConnector;

use Passbook\Pass\Field;
use Passbook\Pass\Image;
use Passbook\PassFactory;
use Passbook\Pass\Barcode;
use Passbook\Pass\Structure;
use Passbook\Type\EventTicket;

/**
 * Provides route responses for the ParkingBooker module.
 */
class ParkingPassbookController extends ControllerBase {

  /**
   * @return array
   *   A simple renderable array.
   */
  public function index($booking_guid = null) {
    $api = new AdvamConnector();

    $booking = $api->getBooking($booking_guid);

    $element = [
      '#markup' => 'Hello, world',
    ];

    // Create an event ticket
    $pass = new EventTicket("1234567890", "The Beat Goes On");
    $pass->setBackgroundColor('rgb(60, 65, 76)');
    $pass->setLogoText('Apple Inc.');

    // Create pass structure
    $structure = new Structure();

    // Add primary field
    $primary = new Field('event', 'The Beat Goes On');
    $primary->setLabel('Event');
    $structure->addPrimaryField($primary);

    // Add secondary field
    $secondary = new Field('location', 'Moscone West');
    $secondary->setLabel('Location');
    $structure->addSecondaryField($secondary);

    // Add auxiliary field
    $auxiliary = new Field('datetime', '2013-04-15 @10:25');
    $auxiliary->setLabel('Date & Time');
    $structure->addAuxiliaryField($auxiliary);

    // Add icon image
    $icon = new Image('/path/to/icon.png', 'icon');
    $pass->addImage($icon);

    // Set pass structure
    $pass->setStructure($structure);

    // Add barcode
    $barcode = new Barcode(Barcode::TYPE_QR, 'barcodeMessage');
    $pass->setBarcode($barcode);

    // Create pass factory instance
    $factory = new PassFactory('PASS-TYPE-IDENTIFIER', 'TEAM-IDENTIFIER', 'ORGANIZATION-NAME', '/path/to/p12/certificate', 'P12-PASSWORD', '/path/to/wwdr/certificate');
    $factory->setOutputPath('/path/to/output/path');
    $factory->package($pass);

    return $element;
  }

}
?>