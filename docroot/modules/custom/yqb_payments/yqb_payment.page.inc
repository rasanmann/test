<?php

/**
 * @file
 * Contains yqb_payment.page.inc.
 *
 * Page callback for Online Payment entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Online Payment templates.
 *
 * Default template: yqb_payment.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_yqb_payment(array &$variables) {
  // Fetch YqbPaymentEntity Entity Object.
  $yqb_payment = $variables['elements']['#yqb_payment'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
