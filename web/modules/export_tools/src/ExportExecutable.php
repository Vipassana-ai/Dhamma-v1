<?php

namespace Drupal\export_tools;

use Drupal\export_tools\Entity\ExportDefinitionInterface;

/**
 * Execute export content.
 */
class ExportExecutable implements ExportExecutableInterface {

  /**
   * The export definition.
   *
   * @var \Drupal\export_tools\Entity\ExportDefinitionInterface
   */
  protected $exportDefinition;

  /**
   * The filepath destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * ExportExecutableInterface constructor.
   *
   * @param \Drupal\export_tools\Entity\ExportDefinitionInterface $exportDefinition
   *   The export definition.
   */
  public function __construct(ExportDefinitionInterface $exportDefinition) {
    $this->exportDefinition = $exportDefinition;
  }

  /**
   * {@inheritDoc}
   */
  public function export(array $entities, $filename = '', $filepath = '', $saveAsTemporary = TRUE): string {
    $destination = $this->exportDefinition->getDestinationPlugin();

    // Deal with file generation options.
    if (!empty($filename)) {
      $destination->setFilename($filename);
      if (empty($filepath)) {
        $filepath = $this->generateDestinationFilepath($filename);
      }
      $destination->setDestination($filepath);
      $destination->saveAsTemporary($saveAsTemporary);
    }

    return $destination->export($entities);
  }

  /**
   * The filepath destination to export to.
   *
   * @param string $filename
   *   The filename to use in destination.
   *
   * @return string
   *   The filepath.
   */
  public function generateDestinationFilepath($filename = ''): string {
    if (empty($filename)) {
      $filename = $this->generateRandomFilename();
    }
    return 'public://exports/' . $filename;
  }

  /**
   * Generate a random filename.
   *
   * @param string $extension
   *   The extension to use.
   *
   * @return string
   *   The randomized filename.
   */
  public function generateRandomFilename($extension = ''): string {
    if (!empty($extension)) {
      $extension = '.' . $extension;
    }
    return 'export-' . substr(hash('ripemd160', uniqid('', TRUE)), 0, 20) . $extension;
  }

}
