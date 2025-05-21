<?php

namespace Drupal\myportal_autologout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure MyPortal autologout settings for this site.
 *
 * @package Drupal\myportal_autologout\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The states pre-defined.
   *
   * @var string[]
   */
  protected $states = ['novpn', 'vpn'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_autologout_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['myportal_autologout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('myportal_autologout.settings');

    $form['use_watchdog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable watchdog Automated Logout logging'),
      '#default_value' => $config->get('use_watchdog'),
      '#description' => $this->t('Enable logging of automatically logged out users'),
    ];

    foreach ($this->states as $state) {
      $config_state = $config->get("state." . $state);

      $form[$state] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings for state in "%state"', ['%state' => $state]),
        '#tree' => TRUE,
      ];

      $form[$state]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable autologout'),
        '#default_value' => $config_state['enabled'] ?? NULL,
        '#weight' => -20,
        '#description' => $this->t("Enable autologout on this site."),
      ];

      $form[$state]['timeout'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Timeout value in seconds'),
        '#default_value' => $config_state['timeout'] ?? NULL,
        '#size' => 8,
        '#required' => TRUE,
        '#description' => $this->t('The length of inactivity time, in seconds, before automated log out. Must be 60 seconds or greater.'),
      ];

      $form[$state]['delay'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Delay value in seconds'),
        '#default_value' => $config_state['delay'] ?? NULL,
        '#size' => 8,
        '#required' => TRUE,
        '#description' => $this->t('The length of time, in seconds, before the automated logout system started after user login.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    foreach ($this->states as $state) {
      $values = $form_state->getValue($state);

      if (!$this->timeValidate($values['delay'], 0, 86400)) {
        $form_state->setErrorByName(
          "{$state}][delay",
          $this->t("The value must be an integer %min seconds or greater and lower than or equal to limit of %max seconds.", [
            '%min' => 0,
            '%max' => 86400,
          ])
        );
      }
      if (!$this->timeValidate($values['timeout'], 60, 86400)) {
        $form_state->setErrorByName(
          "{$state}][timeout",
          $this->t("The value must be an integer %min seconds or greater and lower than or equal to limit of %max seconds.", [
            '%min' => 60,
            '%max' => 86400,
          ])
        );
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('myportal_autologout.settings');

    foreach ($this->states as $state) {
      $values = $form_state->getValue($state);
      $config->set("state.{$state}.enabled", $values['enabled']);
      foreach (['delay', 'timeout'] as $name) {
        $config->set("state.{$state}.{$name}", $values[$name]);
      }
    }

    $config
      ->set('use_watchdog', $form_state->getValue('use_watchdog'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validate time range.
   *
   * @param string|int $time
   *   The time value in seconds to validate.
   * @param string|int $min
   *   (optional) Minimum value of time in seconds.
   * @param string|int $max
   *   (optional) Maximum value of time in seconds.
   *
   * @return bool
   *   Return TRUE or FALSE
   */
  protected function timeValidate($time, $min = 60, $max = 86400) {
    return is_numeric($time) && $time >= 0 && $time >= $min && $time <= $max;
  }

}
