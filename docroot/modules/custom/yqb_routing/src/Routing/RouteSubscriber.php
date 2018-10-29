<?php
/**
 * @file
 * Contains \Drupal\yqb_routing\Routing\RouteSubscriber.
 */



namespace Drupal\yqb_routing\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes',-9999];  // negative Values means "late"
    return $events;
  }
  
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.contact_form.canonical')) {
//      $route->setDefault('_title', 'Test title');
    }
  }
}