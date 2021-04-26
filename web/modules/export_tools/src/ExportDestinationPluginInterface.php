<?php

namespace Drupal\export_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\export_tools\Entity\ExportDefinitionInterface;

/**
 * Defines an interface for exports.
 *
 * @see \Drupal\export_tools\Annotation\ExportDestination
 * @see \Drupal\export_tools\ExportDestinationPluginBase
 * @see \Drupal\export_Tools\ExportDestinationPluginManager
 * @see plugin_api
 */
interface ExportDestinationPluginInterface {

  /**
   * Set the export definition.
   *
   * @param \Drupal\export_tools\Entity\ExportDefinitionInterface $exportDefinition
   *   The export definition.
   */
  public function setExportDefinition(ExportDefinitionInterface $exportDefinition): void;

  /**
   * Process the export.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to export.
   *
   * @return string
   *   The output result.
   */
  public function export(array $entities): string;

  /**
   * The export process.
   *
   * @param array $entities
   *   Entities to export.
   */
  public function processExport(array $entities): void;

  /**
   * Process the field data.
   *
   * @param string $key
   *   The key definition.
   * @param array $field
   *   The field definition.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity containing the data.
   *
   * @return string
   *   The data result.
   */
  public function process($key, array $field, EntityInterface $entity): string;

  /**
   * Save the export as file.
   *
   * @param string $filename
   *   The file name to save.
   * @param string $destination
   *   The file path destination to save to. Include the filename.
   */
  public function save($filename, $destination): void;

  /**
   * Define the filename to export.
   *
   * @param string $filename
   *   The file name.
   */
  public function setFilename(string $filename): void;

  /**
   * Define the filepath destination to export to.
   *
   * @param string $destination
   *   The filepath.
   */
  public function setDestination(string $destination): void;

  /**
   * Define if the saved file has to be generated as temporary.
   *
   * @param bool $asTemporary
   *   TRUE if file has to be generated as temporary file.
   */
  public function saveAsTemporary(bool $asTemporary): void;

}
