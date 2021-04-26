<?php

namespace Drupal\date_range_picker\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

/**
 * Plugin implementation of the 'date_picker_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "date_range_picker_field_widget",
 *   module = "date_range_picker",
 *   label = @Translation("Date range picker"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangePickerFieldWidget extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#attached']['library'][] = 'date_range_picker/litepicker';

    $uniqueString = implode('-', array_merge($element['#field_parents'], [$items->getName(), $delta]));
    $uniqueId = Html::getUniqueId($uniqueString);

    $start = $element['value']['#default_value'];
    $end = $element['end_value']['#default_value'];

    if ($start) {
      $element['#attached']['drupalSettings']['date_range_picker'][$uniqueId]['start'] = $start->format('Y-m-d');
    }

    if ($end) {
      $element['#attached']['drupalSettings']['date_range_picker'][$uniqueId]['end'] = $end->format('Y-m-d');
    }

    $element['litepicker'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'id' => $uniqueId,
        'class' => [
          'litepicker-input',
        ],
      ],
    ];

    $element['value']['#attributes'] = [
      'class' => [
        'start',
      ],
    ];

    $element['end_value']['#attributes'] = [
      'class' => [
        'end',
      ],
    ];

    return $element;
  }

  /**
   * This widget is only available for date range fields without time.
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getSetting('datetime_type') === 'date';
  }

}
