<?php

namespace Drupal\export_tools\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\export_tools\ExportDestinationPluginInterface;

/**
 * Interface for export definitions.
 */
interface ExportDefinitionInterface extends ConfigEntityInterface {

  /**
   * Returns the initialized destination plugin.
   *
   * @return \Drupal\export_tools\ExportDestinationPluginInterface
   *   The destination plugin.
   */
  public function getDestinationPlugin(): ExportDestinationPluginInterface;

}
