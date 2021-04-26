<?php

namespace Drupal\export_tools\Plugin\export_tools\Destination;

use Drupal\export_tools\Annotation\ExportDestination;

/**
 * Provides Spreadsheet XLSX destination plugin.
 *
 * @ExportDestination(
 *   id = "spreadsheet.xlsx"
 * )
 */
class SpreadsheetXlsxDestination extends SpreadsheetBaseDestination {

  /**
   * The spreadsheet extension to save.
   *
   * @var string
   */
  protected $extension = 'xlsx';

}
