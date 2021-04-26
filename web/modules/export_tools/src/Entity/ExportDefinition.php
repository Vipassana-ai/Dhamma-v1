<?php

namespace Drupal\export_tools\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\export_tools\ExportDestinationPluginInterface;

/**
 * Defines the Export definition entity.
 *
 * The migration entity stores the information about a single export definition,
 * like fields and destination plugins.
 *
 * @ConfigEntityType(
 *   id = "export_definition",
 *   label = @Translation("Export definition"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "fields",
 *     "destination",
 *   },
 * )
 */
class ExportDefinition extends ConfigEntityBase implements ExportDefinitionInterface {

  /**
   * The migration ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label for the migration.
   *
   * @var string
   */
  protected $label;

  /**
   * The destination configuration, with at least a 'plugin' key.
   *
   * Used to initialize $destinationPlugin.
   *
   * @var array
   */
  protected $destination;

  /**
   * The destination plugin.
   *
   * @var \Drupal\export_tools\ExportDestinationPluginInterface
   */
  protected $destinationPlugin;

  /**
   * The destination plugin manager.
   *
   * @var \Drupal\export_tools\ExportDestinationPluginManager
   */
  protected $destinationPluginManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->destinationPluginManager = \Drupal::service('plugin.manager.export_tools.destination');
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationPlugin(): ExportDestinationPluginInterface {
    if (!isset($this->destinationPlugin)) {
      $this->destinationPlugin = $this->destinationPluginManager->createInstance($this->destination['plugin'], [], $this);
    }
    return $this->destinationPlugin;
  }

  /**
   * The fields definition.
   *
   * @return array
   *   The fields definition.
   */
  public function getFields(): array {
    return $this->fields;
  }

}
