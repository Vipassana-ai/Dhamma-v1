<?php

namespace Drupal\export_tools\Plugin\export_tools\Destination;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\export_tools\ExportDestinationPluginBase;
use Drupal\export_tools\ExportFieldProcessPluginManager;
use Drupal\export_tools\ExportToolsSkipRowException;
use Drupal\export_tools\Helper\SpreadsheetGeneratorHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Spreadsheet base destination plugin.
 */
abstract class SpreadsheetBaseDestination extends ExportDestinationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The headers.
   *
   * @var array
   */
  protected $headers;

  /**
   * The rows.
   *
   * @var array
   */
  protected $rows;

  /**
   * The spreadsheet generator.
   *
   * @var \Drupal\export_tools\Helper\SpreadsheetGeneratorHelper
   */
  protected $spreadsheetGenerator;

  /**
   * The spreadsheet extension to save.
   *
   * @var string
   */
  protected $extension = 'csv';

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
   * @param \Drupal\export_tools\Helper\SpreadsheetGeneratorHelper $spreadsheetGenerator
   *   The spreadsheet generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExportFieldProcessPluginManager $exportFieldProcessPluginManager, SpreadsheetGeneratorHelper $spreadsheetGenerator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $exportFieldProcessPluginManager);
    $this->spreadsheetGenerator = $spreadsheetGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.export_field_process'),
      $container->get('export_tools.generator.spreadsheet')
    );
  }

  /**
   * {@inheritDoc}
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function export(array $entities, $filename = '', $destination = ''): string {
    $this->processExport($entities);
    $this->spreadsheetGenerator->setHeaders($this->headers);
    $this->spreadsheetGenerator->addRows($this->rows);
    return $this->spreadsheetGenerator->generate($this->extension, $filename, $destination, $this->saveFileAsTemporary);
  }

  /**
   * The export process.
   *
   * @param array $entities
   *   Entities to export.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function processExport(array $entities): void {
    $this->headers = $this->getHeader();
    $rows = [];
    foreach ($entities as $entity) {
      $rows[] = $this->getRow($entity);
    }
    $this->rows = $rows;
  }

  /**
   * Save the result in file.
   *
   * @param string $filename
   *   The filename to save.
   * @param string $destination
   *   The destination to save to.
   */
  public function save($filename, $destination): void {
    // Nothing to do, save is already done by spreadsheet.
  }

  /**
   * Get header.
   *
   * @return array
   *   The header cells.
   */
  protected function getHeader(): array {
    $header = [];
    $fields = $this->getFields();
    foreach ($fields as $field) {
      $header[] = $field['label'] ?? '';
    }
    return $header;
  }

  /**
   * Get row from entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The row array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getRow(EntityInterface $entity): array {
    $row = [];
    foreach ($this->getFields() as $key => $field) {
      try {
        $row[] = $this->process($key, $field, $entity);
      }
      catch (ExportToolsSkipRowException $exception) {
        // TODO: Call logger here.
        $row[] = '';
      }
    }

    return $row;
  }

}
