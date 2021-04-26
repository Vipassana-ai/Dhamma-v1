<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportToolsException;

/**
 * Concatenates a set of strings.
 *
 * The concat plugin is used to concatenate strings. For example, imploding a
 * set of strings into a single string.
 *
 * Available configuration keys:
 * - delimiter: (optional) A delimiter, or glue string, to insert between the
 *   strings.
 *
 * Examples:
 *
 * @code
 * fields:
 *   field_text_field:
 *     plugin: concat
 *     source:
 *       - field_subtitle
 * @endcode
 *
 * This will set field_text_field to the concatenation of the 'field_subtitle'
 * source values. For example, if the 'field_text_field' value is "wambooli"
 * and the 'field_subtitle' value is "pastafazoul", the processed output will be
 * "wamboolipastafazoul".
 *
 * You can also specify a delimiter.
 *
 * @code
 * fields:
 *   field_text_field:
 *     plugin: concat
 *     source:
 *       - field_subtitle
 *       - field_another
 *     delimiter: /
 * @endcode
 *
 * This will set field_text_field to the concatenation of the 'field_subtitle'
 * source value, the delimiter and the 'field_another' source value. For
 * example, using the values above and "/" as the delimiter, if the
 * 'field_subtitle' value is "wambooli" and the 'field_another' source is
 * "pastafazoul", field_text_field will be "wambooli/pastafazoul".
 *
 * @see \Drupal\export_tools\ExportFieldProcessPluginInterface
 *
 * @ExportFieldProcess(
 *   id = "concat",
 *   handle_multiples = TRUE
 * )
 */
class Concat extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($this->configuration['source'])) {
      throw new ExportToolsException(sprintf('"source" must be configured'));
    }

    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    $values = [
      $value,
    ];
    foreach ($this->configuration['source'] as $key) {
      if ($sourceValue = $this->destinationPlugin->process($key, [], $entity)) {
        $values[] = $sourceValue;
      }
    }

    $delimiter = $this->configuration['delimiter'] ?? '';
    return implode($delimiter, $values);
  }

}
