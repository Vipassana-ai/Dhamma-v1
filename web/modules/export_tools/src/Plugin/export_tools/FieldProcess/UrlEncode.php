<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\export_tools\ExportToolsException;
use GuzzleHttp\Psr7\Uri;

/**
 * URL-encodes the input value.
 *
 * Example:
 *
 * @code
 * fields:
 *   field_url:
 *     plugins:
 *     -
 *       plugin: urlencode
 * @endcode
 *
 * This will convert the source URL 'http://example.com/a url with spaces.html'
 * into 'http://example.com/a%20url%20with%20spaces.html'.
 *
 * \Drupal\export_tools\ExportFieldProcessPluginInterface
 *
 * @ExportFieldProcess(
 *   id = "urlencode"
 * )
 */
class UrlEncode extends DefaultFieldProcess {

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\export_tools\ExportToolsException
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    // Only apply to a full URL.
    if (is_string($value) && strpos($value, '://') > 0) {
      // URL encode everything after the hostname.
      $parsed_url = parse_url($value);
      // Fail on seriously malformed URLs.
      if ($parsed_url === FALSE) {
        throw new ExportToolsException("Value '$value' is not a valid URL");
      }
      // Iterate over specific pieces of the URL rawurlencoding each one.
      $url_parts_to_encode = ['path', 'query', 'fragment'];
      foreach ($parsed_url as $parsed_url_key => $parsed_url_value) {
        if (in_array($parsed_url_key, $url_parts_to_encode, TRUE)) {
          // urlencode() would convert spaces to + signs.
          $urlencoded_parsed_url_value = rawurlencode($parsed_url_value);
          // Restore special characters depending on which part of the URL this
          // is.
          switch ($parsed_url_key) {
            case 'query':
              $urlencoded_parsed_url_value = str_replace('%26', '&', $urlencoded_parsed_url_value);
              break;

            case 'path':
              $urlencoded_parsed_url_value = str_replace('%2F', '/', $urlencoded_parsed_url_value);
              break;
          }

          $parsed_url[$parsed_url_key] = $urlencoded_parsed_url_value;
        }
      }
      $value = (string) Uri::fromParts($parsed_url);
    }
    return $value;
  }

}
