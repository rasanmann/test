<?php

assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

$config_directories = array();

$settings['extension_discovery_scan_tests'] = TRUE;
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = 'sites/default/files/private';
$settings['update_free_access'] = TRUE;
$settings['file_chmod_directory'] = 0775;
$settings['file_chmod_file'] = 0664;

$settings['container_yamls'][] = __DIR__ . '/services.yml';

$databases['default']['default'] = array (
  'database' => 'yqb_development',
  'username' => 'root',
  'password' => '',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

$settings['hash_salt'] = 'muvxYCDkSL08VsMcpVLQIUZwRGJiUSdlby9YRq9ZFylRLeh1MUIhgd2neowII9uEqliR7SZWAw';
$settings['install_profile'] = 'standard';
$config_directories['sync'] = 'sites/yqb.dev/files/config_GNGIARzWYNBvbL7xFdV9ayMF77WX2ao8XRxZkOrzF01UsrPjjNtnQARaWTcX3mJ9TxXSB5raEQ/sync';


if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
   include $app_root . '/' . $site_path . '/settings.local.php';
}

// On Acquia Cloud, this include file configures Drupal to use the correct
// database in each site environment (Dev, Stage, or Prod). To use this
// settings.php for development on your local workstation, set $db_url
// (Drupal 5 or 6) or $databases (Drupal 7 or 8) as described in comments above.
if (file_exists('/var/www/site-php')) {
  require('/var/www/site-php/yqb/yqb-settings.inc');
  
	// Memcache settings.
//  $settings['cache']['default'] = 'cache.backend.memcache';
//  $settings['memcache']['stampede_protection'] = TRUE;
}
