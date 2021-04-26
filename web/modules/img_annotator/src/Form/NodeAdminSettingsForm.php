<?php

declare(strict_types = 1);

namespace Drupal\img_annotator\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure node settings for this site.
 */
class NodeAdminSettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_annotator_node_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['img_annotator.node_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('img_annotator.node_settings');
    $imageFieldsData = $this->getImageFieldsData();

    $form['bundles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bundles'),
      '#tree' => TRUE,
    ];

    foreach ($imageFieldsData as $bundle => $bundle_infos) {
      $form['bundles'][$bundle] = [
        '#type' => 'details',
        '#title' => $bundle_infos['bundle_label'],
        '#open' => TRUE,
      ];

      $form['bundles'][$bundle]['flag'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable image annotation'),
        '#default_value' => $config->get('bundles.' . $bundle . '.flag') ? $config->get('bundles.' . $bundle . '.flag') : FALSE,
      ];

      $form['bundles'][$bundle]['img_fields'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Image fields'),
        '#description' => $this->t('Enable annotations for the selected image fields.'),
        '#options' => $bundle_infos['fields'],
        '#default_value' => $config->get('bundles.' . $bundle . '.img_fields') ? $config->get('bundles.' . $bundle . '.img_fields') : [],
        '#states' => [
          'visible' => [
            ':input[name="bundles[' . $bundle . '][flag]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    $this->config('img_annotator.node_settings')
      ->set('bundles', $form_state->getValue('bundles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Prepare structured data to ease form creation.
   *
   * @return array
   *   The prepared data to create the form.
   */
  protected function getImageFieldsData() : array {
    $data = [];
    $image_fields = $this->entityFieldManager->getFieldMapByFieldType('image');
    foreach ($image_fields as $entity_type_id => $entity_type_fields) {
      if ($entity_type_id != 'node') {
        continue;
      }

      foreach ($entity_type_fields as $field_machine_name => $field_info) {
        foreach ($field_info['bundles'] as $field_instance_bundle) {
          $field_definition = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $field_instance_bundle)[$field_machine_name];

          $data = NestedArray::mergeDeepArray([
            $data, [
              $field_instance_bundle => [
                'bundle_label' => $field_definition->getTargetBundle(),
                'fields' => [
                  $field_machine_name => $field_definition->getLabel(),
                ],
              ],
            ],
          ]);
        }
      }
    }
    return $data;
  }

}
