<?php
\Drupal::logger(drush_get_option('channel'))->notice(drush_get_option('message') . ' - ' . gethostname());