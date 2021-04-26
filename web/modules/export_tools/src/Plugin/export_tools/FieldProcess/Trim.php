<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Provides trim process plugin.
 *
 * @ExportFieldProcess(
 *   id = "trim"
 * )
 */
class Trim extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    return trim($value);
  }

}
