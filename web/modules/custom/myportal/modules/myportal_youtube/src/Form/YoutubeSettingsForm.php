<?php

namespace Drupal\myportal_youtube\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure myportal_youtube settings.
 */
class YoutubeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_youtube_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['myportal_youtube.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('myportal_youtube.settings');

    $form['api_key_name'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Youtube API Key'),
      '#description' => $this->t('Google API key used to access Youtube services.'),
      '#empty_option' => $this->t('- Select Key -'),
      '#default_value' => $config->get('api_key_name'),
      '#key_filters' => ['type' => 'authentication'],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('myportal_youtube.settings')
      ->set('api_key_name', $form_state->getValue('api_key_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
