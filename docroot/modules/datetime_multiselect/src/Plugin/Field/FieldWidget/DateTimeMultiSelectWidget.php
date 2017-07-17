<?php

namespace Drupal\datetime_multiselect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'datetime_multiselect' widget.
 *
 * @FieldWidget(
 *   id = "datetime_multiselect",
 *   label = @Translation("Multi-select"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeMultiSelectWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

//    return $element;

    $element['value'] += [
      '#element_validate' => [
        [$this, 'validate']
      ],
      '#attached' => [
        'library' => [
          'datetime_multiselect/calendar',
        ],
      ],
    ];

    return $element;
  }

  public function validate(&$element, FormStateInterface $form_state, &$complete_form) {
    
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    // All values are stored in the first element of the array
    $dates = explode(';', array_shift($values)['value']['date']);

    // Rebuild array from scratch
    $values = [];

    // Assign value, weight and original delta
    foreach($dates as $key => &$date) {
      $values[] = [
        'value' => $date,
        '_weight' => 0,
        '_original_delta' => $key
      ];
    }

    return $values;
  }
}
