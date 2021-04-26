<?php

namespace Drupal\export_tools\Plugin\export_tools\Destination;

use Drupal\export_tools\Annotation\ExportDestination;

/**
 * Provides Spreadsheet ODS destination plugin.
 *
 * @ExportDestination(
 *   id = "spreadsheet.ods"
 * )
 */
class SpreadsheetOdsDestination extends SpreadsheetBaseDestination {

  /**
   * The spreadsheet extension to save.
   *
   * @var string
   */
  protected $extension = 'ods';

}
