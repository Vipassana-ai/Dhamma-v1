<?php

namespace Drupal\export_tools\Plugin\export_tools\Destination;

use Drupal\export_tools\Annotation\ExportDestination;

/**
 * Provides SpreadsheetCsv destination plugin.
 *
 * @ExportDestination(
 *   id = "spreadsheet.csv"
 * )
 */
class SpreadsheetCsvDestination extends SpreadsheetBaseDestination {}
