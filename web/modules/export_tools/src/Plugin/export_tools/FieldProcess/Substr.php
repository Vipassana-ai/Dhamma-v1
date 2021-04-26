<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportToolsException;

/**
 * Returns a substring of the input value.
 *
 * The substr process plugin returns the portion of the input value specified by
 * the start and length parameters. This is a wrapper around mb_substr().
 *
 * Available configuration keys:
 * - start: (optional) The returned string will start this many characters after
 *   the beginning of the string, defaults to 0.
 * - length: (optional) The maximum number of characters in the returned
 *   string, defaults to NULL.
 *
 * If start is 0 and length is an integer, the start position is the
 * beginning of the string. If start is an integer and length is NULL, the
 * substring starting from the start position until the end of the string will
 * be returned. If start is 0 and length is NULL the entire string is returned.
 *
 * Example:
 *
 * @code
 * fields:
 *   field_text:
 *     plugin: substr
 *     start: 6
 *     length: 10
 * @endcode
 * If field_text was 'Marie Skłodowska Curie' then value would be 'Skłodowska'.
 *
 * The PHP equivalent of this is:
 * @code
 * $value = substr($source['field_text'], 6, 10);
 * @endcode
 *
 * The substr plugin requires that the source value is not empty. If empty
 * values are expected, combine skip_on_empty process plugin to the pipeline:
 * @code
 * process:
 *   field_text:
 *    -
 *      plugin: skip_on_empty
 *      method: process
 *    -
 *      plugin: substr
 *      start: 6
 *      length: 10
 * @endcode
 *
 * @see \Drupal\export_tools\ExportFieldProcessPluginInterface
 *
 * @ExportFieldProcess(
 *   id = "substr"
 * )
 */
class Substr extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\export_tools\ExportToolsException
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    $start = $this->configuration['start'] ?? 0;
    if (!is_int($start)) {
      throw new ExportToolsException('The start position configuration value should be an integer. Omit this key to capture from the beginning of the string.');
    }
    $length = $this->configuration['length'] ?? NULL;
    if ($length !== NULL && !is_int($length)) {
      throw new ExportToolsException('The character length configuration value should be an integer. Omit this key to capture from the start position to the end of the string.');
    }
    if (!is_string($value)) {
      throw new ExportToolsException('The input value must be a string.');
    }

    // Use optional start or length to return a portion of $value.
    return mb_substr($value, $start, $length);
  }

}
