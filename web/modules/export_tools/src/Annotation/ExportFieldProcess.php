<?php

namespace Drupal\export_tools\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an export field process annotation object.
 *
 * Plugin namespace: Plugin\export_tools\export_field_process.
 *
 * @see \Drupal\export_tools\ExportDestinationPluginBase
 * @see \Drupal\export_tools\ExportDestinationPluginInterface
 * @see \Drupal\export_tools\ExportDestinationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ExportFieldProcess extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
