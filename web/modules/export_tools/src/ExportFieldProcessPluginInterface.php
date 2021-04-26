<?php

namespace Drupal\export_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for export field processes.
 *
 * @see \Drupal\export_tools\Annotation\ExportFieldProcess
 * @see \Drupal\export_tools\ExportFieldProcessPluginBase
 * @see \Drupal\export_Tools\ExportFieldProcessPluginManager
 * @see plugin_api
 */
interface ExportFieldProcessPluginInterface {

  /**
   * Set the export destination Plugin.
   *
   * @param \Drupal\export_tools\ExportDestinationPluginInterface $destinationPlugin
   *   The export destination plugin.
   */
  public function setExportDestinationPlugin(ExportDestinationPluginInterface $destinationPlugin): void;

  /**
   * Process the field item from the entity.
   *
   * @param array $components
   *   The component.
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   *   The field item.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $value
   *   The already processed value result.
   *
   * @return string
   *   The exported data.
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string;

}
