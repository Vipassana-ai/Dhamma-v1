<?php

namespace Drupal\export_tools\Plugin\export_tools\Destination;

use Drupal\export_tools\Annotation\ExportDestination;

/**
 * Provides Spreadsheet XLS destination plugin.
 *
 * @ExportDestination(
 *   id = "spreadsheet.xls"
 * )
 */
class SpreadsheetXlsDestination extends SpreadsheetBaseDestination {

  /**
   * The spreadsheet extension to save.
   *
   * @var string
   */
  protected $extension = 'xls';

}
