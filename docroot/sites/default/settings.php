<?php

assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

$config_directories = array();
ini_set('memory_limit', '2000M');
$settings['extension_discovery_scan_tests'] = TRUE;
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = 'sites/default/files/private';
$settings['update_free_access'] = TRUE;
$settings['file_chmod_directory'] = 0775;
$settings['file_chmod_file'] = 0664;
$settings['update_free_access'] = false;

$settings['config_sync_directory'] = 'sites/default/sync';

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
$config_directories['sync'] = 'sites/default/config_e54e53608713265b4345d01a75e15a6750050660/sync';


if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
   include $app_root . '/' . $site_path . '/settings.local.php';
}

$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;
// On Acquia Cloud, this include file configures Drupal to use the correct
// database in each site environment (Dev, Stage, or Prod). To use this
// settings.php for development on your local workstation, set $db_url
// (Drupal 5 or 6) or $databases (Drupal 7 or 8) as described in comments above.
if (file_exists('/var/www/site-php')) {
  require('/var/www/site-php/yqb/yqb-settings.inc');

  $lowCache = [
  		'/',
		  '/fr',
		  '/en',
		  '/fr/vols-et-destinations/horaire-des-vols/arrivees',
		  '/fr/vols-et-destinations/horaire-des-vols/departs',
		  '/fr/vols-et-destinations/horaire-des-vols/arrivees-demain',
		  '/fr/vols-et-destinations/horaire-des-vols/departs-demain',
		  '/en/flights-and-destinations/flight-schedules/departures',
		  '/en/flights-and-destinations/flight-schedules/departures-tomorrow',
		  '/en/flights-and-destinations/flight-schedules/arrivals',
		  '/en/flights-and-destinations/flight-schedules/arrivals-tomorrow',
  ];

	if (in_array($_SERVER['SCRIPT_URL'], $lowCache)) {
//		 Set this page to only be cached externally for 30 seconds.
		$GLOBALS['conf']['page_cache_maximum_age'] = 300;
	}

	// Memcache settings.
  $settings['cache']['default'] = 'cache.backend.memcache';
  $settings['memcache']['stampede_protection'] = TRUE;


}
