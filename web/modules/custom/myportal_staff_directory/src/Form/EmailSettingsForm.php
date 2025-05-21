<?php

namespace Drupal\myportal_staff_directory\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class EmailSettingsForm extends ConfigFormBase {

  /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'myportal_staff_directory.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_staff_directory_settings';
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
    
    $form['recipients'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient email address'),
      '#description' => $this->t('Multiple addresses comma separated'),
      '#default_value' => $config->get('recipients'),
    ];  

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('recipients', $form_state->getValue('recipients'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}