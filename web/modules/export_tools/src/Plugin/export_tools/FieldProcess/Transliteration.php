<?php

namespace Drupal\export_tools\Plugin\export_tools\FieldProcess;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transliterates text from Unicode to US-ASCII.
 *
 * The transliteration process plugin takes the source value and runs it through
 * the transliteration service. Letters will have language decorations and
 * accents removed.
 *
 * Example:
 *
 * @code
 * field_text:
 *   plugins:
 *     -
 *       plugin: transliteration
 * @endcode
 *
 * If the value of foo in the source is 'áéí!' then the destination value of
 * bar will be 'aei!'.
 *
 * @see \Drupal\export_tools\ExportFieldProcessPluginInterface
 *
 * @ExportFieldProcess(
 *   id = "transliteration"
 * )
 */
class Transliteration extends DefaultFieldProcess implements ContainerFactoryPluginInterface {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a Transliteration plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function process(array $components, FieldItemInterface $fieldItem, EntityInterface $entity, $value): string {
    if (empty($value)) {
      $value = parent::process($components, $fieldItem, $entity, $value);
    }

    return $this->transliteration->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
  }

}
