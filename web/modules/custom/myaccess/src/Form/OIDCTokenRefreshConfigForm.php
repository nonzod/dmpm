<?php

namespace Drupal\myaccess\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class OIDCTokenRefreshConfigForm extends ConfigFormBase{
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'myaccess.oidc_token_refresh_settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oidc_token_refresh_settings';
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

    $form['refresh_mode'] = [
      '#type' => 'select',
      '#options' => [
        'disabled' => $this->t('Disabled'),
        'ping' => $this->t('Ping only'),
        'full' => $this->t('Full'),
      ],
      '#description' => $this->t(
        '<b>Disabled</b>: no refresh is performed and the functionality is completely disabled<br>
                <b>Ping only</b>: make ajax calls, but without logging the user out in case of error or token expiration<br>
                <b>Full</b>: log out the user in case of error or token expiration'),
      '#title' => $this->t('Token Refresh Mode'),
      '#default_value' => $config->get('refresh_mode') ? $config->get('refresh_mode') : 'disabled',
    ];
    $form['refresh_time_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval of ajax polling'),
      '#description' => $this->t('In minutes. The time interval between ajax calls to refresh the token (min 1, max 29, recommended 12).'),
      '#min' => 1,
      '#max' => 29,
      '#default_value' => $config->get('refresh_time_interval') ? $config->get('refresh_time_interval') : 12,
      '#states' => [
        'visible' => [
          ':input[name="refresh_mode"]' => ['!value' => 'disabled'],
        ],
      ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('refresh_mode', $form_state->getValue('refresh_mode'))
      ->set('refresh_time_interval', $form_state->getValue('refresh_time_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
