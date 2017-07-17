<?php
// Usage : drush scr tools/tasks/clear-api-cache.php
// Cache clearer that aims to clear API routing cache

// Based on core/includes/common.inc

use Drupal\Core\Cache\Cache;

$module_handler = \Drupal::moduleHandler();

// Flush all persistent caches.
// This is executed based on old/previously known information, which is
// sufficient, since new extensions cannot have any primed caches yet.
/*
 * Available cache bins :
 * static
 * bootstrap
 * config
 * default
 * entity
 * menu
 * render
 * data
 * discovery
 * dynamic_page_cache
 * migrate
 * rest
 * toolbar
 */
$module_handler->invokeAll('cache_flush');
foreach (Cache::getBins() as $service_id => $cache_backend) {
    if (in_array($service_id, ['data'])) {
        $cache_backend->deleteAll();
    }
}

\Drupal::service('asset.css.collection_optimizer')->deleteAll();
\Drupal::service('asset.js.collection_optimizer')->deleteAll();
_drupal_flush_css_js();