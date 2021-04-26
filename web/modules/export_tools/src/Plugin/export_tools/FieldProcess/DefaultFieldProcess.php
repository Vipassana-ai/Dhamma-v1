<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportFieldProcessPluginBase;

/**
 * Provides default process plugin.
 *
 * @ExportFieldProcess(
 *   id = "default"
 * )
 */
class DefaultFieldProcess extends ExportFieldProcessPluginBase {

  /**
   * {@inheritDoc}
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    // If a value has already be processed, don't do anything more.
    if (!empty($value)) {
      return $value;
    }

    if ($components['property'] === NULL) {
      return $fieldItem->getString();
    }

    $value = $fieldItem->getValue();
    return $value[$components['property']] ?? '';
  }

}
