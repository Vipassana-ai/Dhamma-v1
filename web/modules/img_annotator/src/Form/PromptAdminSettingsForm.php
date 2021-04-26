<?php

declare(strict_types = 1);

namespace Drupal\img_annotator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure prompt settings for this site.
 */
class PromptAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_annotator_prompt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'img_annotator.prompt_settings',
      'img_annotator.prompt_message',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prompt Style.
    $prompt_settings = $this->config('img_annotator.prompt_settings');

    $form['prompt_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Prompt details'),
      '#open' => TRUE,
    ];
    $form['prompt_details']['prompt'] = [
      '#type' => 'radios',
      '#title' => $this->t('Prompt style'),
      '#options' => [
        '0' => $this->t('None'),
        'js' => $this->t('Show JavaScript alerts'),
      ],
      '#default_value' => $prompt_settings->get('prompt'),
    ];

    // Prompt Messages.
    $prompt_message = $this->config('img_annotator.prompt_message');

    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Prompt messages'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $messages = [
      'addSuccess' => $this->t('On Add [Success]'),
      'addFailed' => $this->t('On Add [Failed]'),
      'addNotAllowed' => $this->t('On Add [Not Allowed]'),
      'removeSuccess' => $this->t('On Remove [Success]'),
      'removeFailed' => $this->t('On Remove [Failed]'),
      'updateSuccess' => $this->t('On Update [Success]'),
      'updateFailed' => $this->t('On Update [Failed]'),
    ];

    foreach ($messages as $key => $message_label) {
      $form['message'][$key] = [
        '#type' => 'textfield',
        '#title' => $message_label,
        '#default_value' => $prompt_message->get($key),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    $prompt_settings = $this->config('img_annotator.prompt_settings');
    $prompt_settings
      ->set('prompt', $form_state->getValue('prompt'))
      ->save();

    $prompt_message = $this->config('img_annotator.prompt_message');
    foreach ($form_state->getValue('message') as $key => $message) {
      $prompt_message->set($key, $message);
    }
    $prompt_message->save();

    parent::submitForm($form, $form_state);
  }

}
