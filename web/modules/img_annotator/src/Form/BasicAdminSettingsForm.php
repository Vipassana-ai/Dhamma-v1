<?php

declare(strict_types = 1);

namespace Drupal\img_annotator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure basic settings for this site.
 */
class BasicAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_annotator_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['img_annotator.basic_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('img_annotator.basic_settings');

    $form['anno_theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Annotation theme'),
      '#options' => [
        'basic' => $this->t('Basic'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('anno_theme'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    parent::submitForm($form, $form_state);

    $this->config('img_annotator.basic_settings')
      ->set('anno_theme', $form_state->getValue('anno_theme'))
      ->save();
  }

}
