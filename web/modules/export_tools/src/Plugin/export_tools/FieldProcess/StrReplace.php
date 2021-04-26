<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportToolsException;

/**
 * Uses the str_replace() method on a source string.
 *
 * @ExportFieldProcess(
 *   id = "str_replace"
 * )
 *
 * @codingStandardsIgnoreStart
 *
 * To do a simple hardcoded string replace, use the following:
 * @code
 * field_text:
 *   plugins:
 *     -
 *       plugin: str_replace
 *       search: foo
 *       replace: bar
 * @endcode
 * If the value of text is "vero eos et accusam et justo vero" in source, foo is
 * "et" in search and bar is "that" in replace, field_text will be "vero eos
 * that accusam that justo vero".
 *
 * Case insensitive searches can be achieved using the following:
 * @code
 * field_text:
 *   plugins:
 *     -
 *       plugin: str_replace
 *       case_insensitive: true
 *       search: foo
 *       replace: bar
 * @endcode
 * If the value of text is "VERO eos et accusam et justo vero" in source, foo is
 * "vero" in search and bar is "that" in replace, field_text will be "that eos
 * et accusam et justo that".
 *
 * Also regular expressions can be matched using:
 * @code
 * field_text:
 *   plugins:
 *     -
 *       plugin: str_replace
 *       regex: true
 *       search: foo
 *       replace: bar
 * @endcode
 * If the value of text is "vero eos et 123 accusam et justo 123 duo" in source,
 * foo is "/[0-9]{3}/" in search and bar is "the" in replace, field_text will be
 * "vero eos et the accusam et justo the duo".
 *
 * All the rules for
 * @link http://php.net/manual/function.str-replace.php str_replace @endlink
 * apply. This means that you can provide arrays as values.
 *
 * Multiple values can be matched like this:
 * @code
 * field_text:
 *   plugins:
 *     -
 *       plugin: str_replace
 *       search: ["AT", "CH", "DK"]
 *       replace: ["Austria", "Switzerland", "Denmark"]
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 */
class StrReplace extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\export_tools\ExportToolsException
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    if (!isset($this->configuration['search'])) {
      throw new ExportToolsException('"search" must be configured.');
    }
    if (!isset($this->configuration['replace'])) {
      throw new ExportToolsException('"replace" must be configured.');
    }

    $this->configuration += [
      'case_insensitive' => FALSE,
      'regex' => FALSE,
    ];
    $function = 'str_replace';
    if ($this->configuration['case_insensitive']) {
      $function = 'str_ireplace';
    }
    if ($this->configuration['regex']) {
      $function = 'preg_replace';
    }
    return $function($this->configuration['search'], $this->configuration['replace'], $value);
  }

}
