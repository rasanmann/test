<?php

namespace Drupal\pjax\EventSubscriber;

use Drupal\Core\EventSubscriber;
use Drupal\Core\EventSubscriber\ActiveLinkResponseFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to filter HTML responses, to set the 'is-active' class on links.
 *
 * Only for anonymous users; for authenticated users, the active-link asset
 * library is loaded.
 *
 * @see system_page_attachments()
 */
class PjaxActiveLinkResponseFilter extends ActiveLinkResponseFilter implements EventSubscriberInterface {
  public static function setLinkActiveClass($html_markup, $current_path, $is_front, $url_language, array $query) {
    // Simply unset pjax query string key-values to prevent mix up in active links classes
    unset($query['_pjax']);
    unset($query['_']);

    return parent::setLinkActiveClass($html_markup, $current_path, $is_front, $url_language, $query);
  }
}
