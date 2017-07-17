<?php

assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

$config_directories = array();

$config['system.site']['name'] = 'yqb - DEVELOPMENT';
$config['system.logging']['error_level'] = 'verbose';
$config['system.performance']['cache']['page']['max_age'] = 0;  // Time in seconds, 0 = no caching
$config['dblog.settings']['row_limit'] = 1000;
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
$config['system.performance']['css']['gzip'] = FALSE;
$config['system.performance']['js']['gzip'] = FALSE;
$config['system.performance']['response']['gzip'] = FALSE;
$config['system.cron']['threshold']['autorun'] = 0;
$config['system.file']['path']['temporary'] = '/tmp';
$config['system.file']['temporary_maximum_age'] = 21600;

$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

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
