<?php
// Usage : drush scr tools/tasks/clear-api-cache.php
// Cache clearer that aims to clear API routing cache

// Based on core/includes/common.inc

use Drupal\Core\Cache\Cache;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Render\Element;

// New container, new module handler.
$module_handler = \Drupal::moduleHandler();

// Rebuild all information based on new module data.
$module_handler->invoke('yqb_api', 'rebuild');

// Clear all plugin caches.
\Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();


// Rebuild all information based on new module data.
$module_handler->invoke('yqb_helpdesk', 'rebuild');

// Clear all plugin caches.
\Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();


// Rebuild all information based on new module data.
$module_handler->invoke('yqb_reminders', 'rebuild');

// Clear all plugin caches.
\Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

// Rebuild the menu router based on all rebuilt data.
// Important: This rebuild must happen last, so the menu router is guaranteed
// to be based on up to date information.
\Drupal::service('router.builder')->rebuild();