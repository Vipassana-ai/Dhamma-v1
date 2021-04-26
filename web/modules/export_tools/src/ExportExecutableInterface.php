<?php

namespace Drupal\export_tools;

/**
 * Execute export content interface.
 */
interface ExportExecutableInterface {

  /**
   * Export the defined entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to export.
   * @param string $filename
   *   The filename to generate.
   * @param string $filepath
   *   The filepath destination to create file.
   * @param bool $saveAsTemporary
   *   Save destination file as temporary file.
   *
   * @return string
   *   The output result.
   */
  public function export(array $entities, $filename = '', $filepath = '', $saveAsTemporary = TRUE): string;

}
