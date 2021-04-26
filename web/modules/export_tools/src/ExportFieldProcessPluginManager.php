<?php

namespace Drupal\export_tools;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\export_tools\Annotation\ExportFieldProcess;

/**
 * Provides an ExportFieldProcess plugin manager.
 *
 * @see \Drupal\export_tools\Annotation\ExportFieldProcess
 * @see \Drupal\export_tools\ExportFieldProcessPluginBase
 * @see \Drupal\export_tools\ExportFieldProcessPluginInterface
 * @see plugin_api
 */
class ExportFieldProcessPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ExportFieldProcessPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
    'Plugin/export_tools/FieldProcess',
    $namespaces,
    $module_handler,
      ExportFieldProcessPluginInterface::class,
      ExportFieldProcess::class
    );
    $this->alterInfo('export_field_process_info');
    $this->setCacheBackend($cache_backend, 'export_tools_plugins_export_field_process');
  }

  /**
   * {@inheritdoc}
   *
   * A specific createInstance method is necessary to pass the destination
   * plugin on.
   */
  public function createInstance($plugin_id, array $configuration = [], ExportDestinationPluginInterface $destinationPlugin = NULL) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // If the plugin provides a factory method, pass the container to it.
    /** @var \Drupal\export_tools\ExportFieldProcessPluginInterface $plugin */
    if (is_subclass_of($plugin_class, ContainerFactoryPluginInterface::class)) {
      // phpcs:ignore
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition);
    }
    $plugin->setExportDestinationPlugin($destinationPlugin);
    return $plugin;
  }

}
