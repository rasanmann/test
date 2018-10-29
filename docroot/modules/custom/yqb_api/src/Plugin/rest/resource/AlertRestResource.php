<?php

namespace Drupal\yqb_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\ResourceResponse;
use Drupal\yqb_reminders\general\Reminders;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "alert_rest_resource",
 *   label = @Translation("Alert rest resource"),
 *   serialization_class = "Drupal\yqb_api\Normalizer\JsonDenormalizer",
 *   uri_paths = {
 *     "canonical" = "/v1/alerts",
 *     "https://www.drupal.org/link-relations/create" = "/v1/alerts"
 *   }
 * )
 */
class AlertRestResource extends YQBResourceBase  {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $current_user);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('yqb_api'),
      $container->get('current_user')
    );
  }


  /**
   * Responds to POST requests.
   *
   * Search for flights on Data Exchange Layer 
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data) {
    /**
     * Fetch Airline
     */
    $airline = Node::load($data['airline']);
    if(empty($airline)) return new ResourceResponse(["error" => $this->t("Airline not found", [], ['langcode' => $this->language])], 404);
    
    $icao = $airline->field_icao->value;
    if($icao === 'JZA' || $icao === 'QZ') $icao = 'ACA';

    /**
     * Prepare and save alert
     */
    $Reminder = new Reminders();
    
    $alert = [];
    $alert['phone_number'] = $Reminder->formatPhoneNumber($data['phone_number']);
    $alert['flight'] = $data['number'];
    $alert['flight_date'] = $data['date'];
    $alert['flight_type'] = $data['type'];
    $alert['flight_airline'] = $icao;
    
    if($Reminder->saveReminder($alert, $this->currentUser)){
      return new ResourceResponse(null, 200);
    }else{
      return new ResourceResponse(["error" => $this->t("Error saving SMS subscription", [], ['langcode' => $this->language])], 400);
    }
  }
}