<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportToolsSkipProcessException;
use Drupal\export_tools\ExportToolsSkipRowException;

/**
 * Skips processing the current row when the input value is empty.
 *
 * The skip_on_empty process plugin checks to see if the current input value
 * is empty (empty string, NULL, FALSE, 0, '0', or an empty array). If so, the
 * further processing of the property or the entire row (depending on the chosen
 * method) is skipped and will not be migrated.
 *
 * Available configuration keys:
 * - method: (optional) What to do if the input value is empty. Possible values:
 *   - row: Skips the entire row when an empty value is encountered.
 *   - process: Prevents further processing of the input property when the value
 *     is empty.
 * - message: (optional) A message to be logged in the {migrate_message_*} table
 *   for this row. Messages are only logged for the 'row' method. If not set,
 *   nothing is logged in the message table.
 *
 * Examples:
 *
 * @code
 * fields:
 *   field_type_exists:
 *     plugins:
 *       -
 *         plugin: skip_on_empty
 *         method: row
 *         source: field_name
 *         message: 'Field field_name is missing'
 * @endcode
 * If 'field_name' is empty, the entire row is skipped and the message 'Field
 * field_name is missing' is logged in the message table.
 *
 * @code
 * fields:
 *   parent:
 *     plugins:
 *       -
 *         plugin: skip_on_empty
 *         method: process
 *         source: field_type_exists
 *     -
 *         plugin: concat
 *         source:
 *          - field_type_exists
 * @endcode
 * If 'field_type_exists' is empty, any further processing of the property is
 * skipped and the next process plugin (concat) will not be run.
 *
 * @see \Drupal\export_tools\ExportFieldProcessPluginInterface
 *
 * @ExportFieldProcess(
 *   id = "skip_on_empty"
 * )
 */
class SkipOnEmpty extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\export_tools\ExportToolsSkipProcessException
   * @throws \Drupal\export_tools\ExportToolsSkipRowException
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    if (empty($this->configuration['source'])) {
      if (!$value) {
        throw (!empty($this->configuration['method']) && $this->configuration['method'] === 'row') ? new ExportToolsSkipRowException() : new ExportToolsSkipProcessException();
      }
      return $value;
    }

    foreach ($this->configuration['source'] as $key) {
      if (empty($this->destinationPlugin->process($key, [], $entity))) {
        throw (!empty($this->configuration['method']) && $this->configuration['method'] === 'row') ? new ExportToolsSkipRowException() : new ExportToolsSkipProcessException();
      }
    }

    return $value;
  }

}
