<?php

namespace Drupal\mediasync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mediasync\Controller\SyncController;

/**
 * Configure example settings for this site.
 */
class MediasyncSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'mediasync.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mediasync_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::SETTINGS);

    $form['folder'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Folder to sync'),
      '#default_value' => $config->get('folder'),
    ];

    $form['type'] = [
      '#type' => 'textarea',
      '#title' => $this->t('File extension => Media type => field name'),
      '#default_value' => $config->get('type'),
    ];
    
    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Media type => field name => vocabulary => ignore list' ),
      '#description' => $this->t('image => fields_tags => tags => photos,test' ),
      '#default_value' => $config->get('tags'),
    ];

    $form['user'] = [
      '#type' => 'select',
      '#title' => $this->t('Owner'),
      '#options' => $this->optionsUserSelect(),
      '#default_value' => $config->get('type'),
    ];

    $form['actions']['statusBtn'] = [
      '#type' => 'submit',
      '#button_type' => 'reset',
      '#name' => 'snycBtn',
      '#value' => $this->t('Synchronization'),
      '#submit' => ['::startSync'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function startSync(array &$form, FormStateInterface $form_state) {
    $sync = new SyncController();
    $sync->sync();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('folder', $form_state->getValue('folder'))
      ->set('type', $form_state->getValue('type'))
      ->set('user', $form_state->getValue('user'))
      ->set('tags', $form_state->getValue('tags'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  private function optionsUserSelect() {

    $userlist = [];

    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');

    $ids = $user_storage->getQuery()
      ->condition('status', 1)
      ->execute();

    $users = $user_storage->loadMultiple($ids);

    foreach ($users as $user) {
      $userlist[$user->id()] = $user->getUsername();
    }

    return $userlist;
  }

}
