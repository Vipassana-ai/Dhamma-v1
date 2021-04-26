<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportFieldProcessPluginBase;

/**
 * Provides Entity reference process plugin.
 *
 * @ExportFieldProcess(
 *   id = "entity_reference"
 * )
 *
 * @codingStandardsIgnoreStart
 *
 * Traverse entity reference field to get informations through referenced
 * entities:
 * @code
 * field_reference:
 *   label: "Reference label"
 *   plugins:
 *     -
 *       plugin: entity_reference
 *       field:
 *         field_label:
 *           plugins:
 *             -
 *               plugin: default
 * @endcode
 *
 * This process can also be used thanks to field key syntax :
 * @code
 * field_reference>field_label:
 *   label: "Reference label"
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 */
class EntityReference extends ExportFieldProcessPluginBase {

  /**
   * {@inheritDoc}
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    // If a value has already be processed, don't do anything more.
    if (!empty($value)) {
      return $value;
    }

    if (!isset($this->configuration['field'])) {
      return '';
    }
    if (!is_array($this->configuration['field'])) {
      $this->configuration['field'] = [
        $this->configuration['field'] => [
          'label' => $this->configuration['field'],
        ],
      ];
    }
    $fields = array_keys($this->configuration['field']);
    $fieldName = reset($fields);

    $referencedEntityStorage = \Drupal::entityTypeManager()->getStorage($fieldItem->getFieldDefinition()->getItemDefinition()->getSetting('target_type'));
    $referencedEntity = $referencedEntityStorage->load($fieldItem->get('target_id')->getString());
    if (NULL === $referencedEntity) {
      return '';
    }
    return $this->destinationPlugin->process($fieldName, $this->configuration['field'][$fieldName], $referencedEntity);
  }

}
