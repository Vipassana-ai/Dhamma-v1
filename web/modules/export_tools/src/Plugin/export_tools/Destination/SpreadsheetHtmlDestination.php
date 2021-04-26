<?php

namespace Drupal\export_tools\Plugin\export_tools\Destination;

use Drupal\export_tools\Annotation\ExportDestination;

/**
 * Provides Spreadsheet HTML destination plugin.
 *
 * @ExportDestination(
 *   id = "spreadsheet.html"
 * )
 */
class SpreadsheetHtmlDestination extends SpreadsheetBaseDestination {

  /**
   * The spreadsheet extension to save.
   *
   * @var string
   */
  protected $extension = 'html';

}
