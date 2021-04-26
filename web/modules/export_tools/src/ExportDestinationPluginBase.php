<?php

namespace Drupal\export_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\export_tools\Entity\ExportDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base export implementation.
 *
 * @see \Drupal\export_tools\Annotation\ExportDestination
 * @see \Drupal\export_tools\ExportDestinationPluginInterface
 * @see \Drupal\export_tools\ExportDestinationPluginManager
 * @see plugin_api
 */
abstract class ExportDestinationPluginBase extends PluginBase implements ExportDestinationPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The export definition.
   *
   * @var \Drupal\export_tools\Entity\ExportDefinitionInterface
   */
  protected $exportDefinition;

  /**
   * The filename to save.
   *
   * @var string
   */
  protected $filename;

  /**
   * The filepath destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * The fields definition.
   *
   * @var array
   */
  protected $fields;

  /**
   * The output.
   *
   * @var string
   */
  protected $output;

  /**
   * Is the file has to be saved temporary ?
   *
   * @var bool
   */
  protected $saveFileAsTemporary = TRUE;

  /**
   * The export field process plugin manager.
   *
   * @var \Drupal\export_tools\ExportFieldProcessPluginManager
   */
  protected $exportFieldProcessPluginManager;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\export_tools\ExportFieldProcessPluginManager $exportFieldProcessPluginManager
   *   The export field process plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExportFieldProcessPluginManager $exportFieldProcessPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->exportFieldProcessPluginManager = $exportFieldProcessPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.export_field_process')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function export(array $entities, $filename = '', $destination = ''): string {
    $this->processExport($entities);
    if (!empty($filename) && !empty($destination)) {
      $this->save($filename, $destination);
    }
    return $this->getOutput();
  }

  /**
   * {@inheritDoc}
   */
  public function setExportDefinition(ExportDefinitionInterface $exportDefinition): void {
    $this->exportDefinition = $exportDefinition;
  }

  /**
   * Get fields definition.
   *
   * @return array
   *   The fields definition.
   */
  public function getFields(): array {
    if (NULL === $this->fields) {
      $this->fields = $this->exportDefinition->getFields();
    }
    return $this->fields;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\export_tools\ExportToolsSkipRowException
   */
  public function process($key, array $field, EntityInterface $entity): string {
    $this->autoResolveDefinitions($key, $field);
    $keyComponents = $this->getKeyComponents($key);

    // Get the current field cardinality.
    if ($keyComponents['cardinality'] === NULL && $entity->hasField($key) && !$entity->get($key)->isEmpty()) {
      $keyComponents['cardinality'] = $entity->get($key)->count();
    }
    if ($keyComponents['cardinality'] !== NULL) {
      $data = [];
      for ($delta = 0; $delta < $keyComponents['cardinality']; $delta++) {
        $deltaComponent = [
          'cardinality' => NULL,
          'delta' => $delta,
        ] + $keyComponents;
        $deltaKey = $this->getStringKeyComponent($deltaComponent);
        try {
          if ($result = $this->process($deltaKey, $field, $entity)) {
            $data[] = $result;
          }
        }
        catch (ExportToolsSkipProcessException $e) {
          // TODO: Add logger here.
          return '';
        }
        catch (ExportToolsSkipRowException $e) {
          // TODO: Add logger here.
          throw $e;
        }
      }
      return implode(', ', $data);
    }

    // Default to delta 0.
    $delta = $keyComponents['delta'] ?? 0;
    $fieldItem = $this->getFieldItem($entity, $keyComponents['fieldname'], $delta);
    // Do not return anything if no delta nor field exists.
    if (NULL === $fieldItem) {
      return '';
    }

    // Process plugins.
    $data = '';
    foreach ($field['plugins'] as $pluginInfo) {
      if (!isset($pluginInfo['plugin'])) {
        continue;
      }
      // Each plugin will define what to do with previous data: should they
      // override or keep it.
      try {
        $data = $this->processPlugin($pluginInfo, $keyComponents, $fieldItem, $entity, $data);
      }
      catch (ExportToolsSkipProcessException $e) {
        // TODO: Add logger here.
        return '';
      }
      catch (ExportToolsSkipRowException $e) {
        // TODO: Add logger here.
        throw $e;
      }
    }

    // Return the processed plugin result.
    return $data;
  }

  /**
   * Get the field item from key for this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $key
   *   The key definition.
   * @param int $delta
   *   The field delta.
   *
   * @return \Drupal\Core\Field\FieldItemInterface|null
   *   The field item.
   */
  protected function getFieldItem(EntityInterface $entity, $key, $delta = 0):? FieldItemInterface {
    return $entity->hasField($key) && !$entity->get($key)->isEmpty() ? $entity->get($key)->get($delta) : NULL;
  }

  /**
   * Automatically resolve definitions to add reference plugins.
   *
   * @param string $key
   *   The key to resolve.
   * @param array $field
   *   The field to alter.
   */
  protected function autoResolveDefinitions(&$key, array &$field): void {
    // Set default plugin if none is defined.
    if (empty($field['plugins'])) {
      $field['plugins'] = [];
      if (isset($field['plugin'])) {
        $field['plugins'] = $field;
      }
      else {
        $field['plugins'][] = ['plugin' => 'default'];
      }
    }

    // Auto generate children entity references plugins.
    if (strpos($key, '>') !== FALSE) {
      $transverseKeys = explode('>', $key);
      // Get the first transverse key as initial key.
      $key = array_shift($transverseKeys);

      // Send the original plugin definition to add it at the last iteration.
      $field['plugins'] = $this->addPluginDefinition($transverseKeys, $field);
    }
  }

  /**
   * Add recursive entity reference plugin definitions.
   *
   * @param array $keys
   *   The entity reference field keys.
   * @param array $defaultField
   *   The default field plugins definition.
   *
   * @return array|mixed
   *   The plugin definitions.
   */
  protected function addPluginDefinition(array $keys, array $defaultField) {
    if (empty($keys)) {
      return $defaultField['plugins'];
    }

    $key = array_shift($keys);
    return [
      [
        'plugin' => 'entity_reference',
        'field' => [
          $key => ['plugins' => $this->addPluginDefinition($keys, $defaultField)],
        ],
      ],
    ];
  }

  /**
   * Get components for a key.
   *
   * @param string $key
   *   The field key.
   *
   * @return array
   *   The key components.
   */
  protected function getKeyComponents($key): array {
    $components = [
      'fieldname' => $key,
      'property' => NULL,
      'cardinality' => NULL,
      'delta' => NULL,
    ];
    // Field name prefixed by /value, will get value property of this
    // field.
    if (strpos($components['fieldname'], '/') !== FALSE) {
      [$fieldName, $property] = explode('/', $key);
      $components['fieldname'] = $fieldName;
      $components['property'] = $property;
    }
    // Field name prefixed by {}, will get number in {} as cardinality.
    if (strpos($components['fieldname'], '{')) {
      [$fieldName, $cardinality] = explode('{', $components['fieldname']);
      $components['fieldname'] = $fieldName;
      $components['cardinality'] = (int) str_replace('}', '', $cardinality);
    }
    // Field name prefixed by [], will get number in [] as delta.
    if (strpos($components['fieldname'], '[')) {
      [$fieldName, $delta] = explode('[', $components['fieldname']);
      $components['fieldname'] = $fieldName;
      $components['delta'] = (int) str_replace(']', '', $delta);
    }

    return $components;
  }

  /**
   * Get key string from component.
   *
   * @param array $component
   *   The key component.
   *
   * @return string
   *   The key.
   */
  protected function getStringKeyComponent(array $component): string {
    $key = $component['fieldname'];
    if (NULL !== $component['cardinality']) {
      $key .= '{' . $component['cardinality'] . '}';
    }
    elseif (NULL !== $component['delta']) {
      $key .= '[' . $component['delta'] . ']';
    }
    if (NULL !== $component['property']) {
      $key .= $component['property'];
    }

    return $key;
  }

  /**
   * Process the plugin to get data.
   *
   * @param array $pluginInfo
   *   The plugin informations.
   * @param array $components
   *   The field key components.
   * @param \Drupal\Core\Field\FieldItemInterface $fieldItem
   *   The field item containing the data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the field item is provided by.
   * @param string $value
   *   The already processed value.
   *
   * @return string
   *   The processed data result.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function processPlugin(array $pluginInfo, array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value = ''): string {
    $plugin = $this->exportFieldProcessPluginManager->createInstance($pluginInfo['plugin'], $pluginInfo, $this);
    return $plugin->process($components, $fieldItem, $entity, $value);
  }

  /**
   * The filename of the export.
   *
   * @return string
   *   The filename.
   */
  public function getFilename(): string {
    return $this->filename;
  }

  /**
   * {@inheritDoc}
   */
  public function setFilename(string $filename): void {
    $this->filename = $filename;
  }

  /**
   * The filepath destination to export to.
   *
   * @return string
   *   The filepath.
   */
  public function getDestination(): string {
    if (empty($this->destination)) {
      $this->destination = 'public://exports/' . $this->generateRandomFilename();
    }
    return $this->destination;
  }

  /**
   * {@inheritDoc}
   */
  public function setDestination(string $destination): void {
    $this->destination = $destination;
  }

  /**
   * The generated output.
   *
   * @return string
   *   The output.
   */
  public function getOutput(): string {
    return $this->output;
  }

  /**
   * Set the generated output.
   *
   * @param string $output
   *   The output.
   */
  public function setOutput(string $output): void {
    $this->output = $output;
  }

  /**
   * {@inheritDoc}
   */
  public function saveAsTemporary(bool $asTemporary): void {
    $this->saveFileAsTemporary = $asTemporary;
  }

}
