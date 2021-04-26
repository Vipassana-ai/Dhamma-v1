<?php

namespace Drupal\export_tools;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base export field process implementation.
 *
 * @see \Drupal\export_tools\Annotation\ExportFieldProcess
 * @see \Drupal\export_tools\ExportFieldProcessPluginInterface
 * @see \Drupal\export_tools\ExportFieldProcessPluginManager
 * @see plugin_api
 */
abstract class ExportFieldProcessPluginBase extends PluginBase implements ExportFieldProcessPluginInterface {

  /**
   * The export destination plugin.
   *
   * @var \Drupal\export_tools\ExportDestinationPluginInterface
   */
  protected $destinationPlugin;

  /**
   * {@inheritDoc}
   */
  public function setExportDestinationPlugin(ExportDestinationPluginInterface $destinationPlugin): void {
    $this->destinationPlugin = $destinationPlugin;
  }

}
