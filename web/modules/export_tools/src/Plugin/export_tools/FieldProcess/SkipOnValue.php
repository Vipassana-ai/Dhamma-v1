<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportToolsException;
use Drupal\export_tools\ExportToolsSkipProcessException;
use Drupal\export_tools\ExportToolsSkipRowException;

/**
 * If the source evaluates to a configured value, skip processing or whole row.
 *
 * @ExportFieldProcess(
 *   id = "skip_on_value"
 * )
 *
 * Available configuration keys:
 * - value: An single value or array of values against which the source value
 *   should be compared.
 * - not_equals: (optional) If set, skipping occurs when values are not equal.
 * - method: What to do if the input value equals to value given in
 *   configuration key value. Possible values:
 *   - row: Skips the entire row.
 *   - process: Prevents further processing of the input property
 *
 * @codingStandardsIgnoreStart
 *
 * Examples:
 *
 * Example usage with minimal configuration:
 * @code
 *   type:
 *     plugin: skip_on_value
 *     method: process
 *     value: blog
 * @endcode
 * The above example will skip further processing of the input property if
 * the content_type value field equals "blog".
 *
 * Example usage with full configuration:
 * @code
 *   type:
 *     plugin: skip_on_value
 *     not_equals: true
 *     method: row
 *     value:
 *       - article
 *       - testimonial
 * @endcode
 * The above example will skip processing any row for which the value row's
 * content type field is not "article" or "testimonial".
 *
 * @codingStandardsIgnoreEnd
 */
class SkipOnValue extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\export_tools\ExportToolsException
   * @throws \Drupal\export_tools\ExportToolsSkipProcessException
   * @throws \Drupal\export_tools\ExportToolsSkipRowException
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }
    if (empty($this->configuration['value']) && !array_key_exists('value', $this->configuration)) {
      throw new ExportToolsException('Skip on value plugin is missing value configuration.');
    }

    if (is_array($this->configuration['value'])) {
      $value_in_array = FALSE;
      $not_equals = isset($this->configuration['not_equals']);

      foreach ($this->configuration['value'] as $skipValue) {
        $value_in_array |= $this->compareValue($value, $skipValue);
      }

      if (($not_equals && !$value_in_array) || (!$not_equals && $value_in_array)) {
        throw (!empty($this->configuration['method']) && $this->configuration['method'] === 'row') ? new ExportToolsSkipRowException() : new ExportToolsSkipProcessException();
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw (!empty($this->configuration['method']) && $this->configuration['method'] === 'row') ? new ExportToolsSkipRowException() : new ExportToolsSkipProcessException();
    }

    return $value;
  }

  /**
   * Compare values to see if they are equal.
   *
   * @param mixed $value
   *   Actual value.
   * @param mixed $skipValue
   *   Value to compare against.
   * @param bool $equal
   *   Compare as equal or not equal.
   *
   * @return bool
   *   True if the compare successfully, FALSE otherwise.
   */
  protected function compareValue($value, $skipValue, $equal = TRUE) {
    if ($equal) {
      return (string) $value == (string) $skipValue;
    }

    return (string) $value != (string) $skipValue;

  }

}
