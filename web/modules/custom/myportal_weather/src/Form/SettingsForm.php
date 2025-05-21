<?php

namespace Drupal\myportal_weather\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\myportal_weather\LocationInterface;
use Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the SettingsForm class.
 *
 * @package Drupal\myportal_weather\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The geocoding service.
   *
   * @var \Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface
   */
  protected GeocodingProviderInterface $geocodingProvider;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_weather_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('myportal_weather.settings');

    $form['openweathermap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OpenWeatherMap Settings'),
      '#tree' => TRUE,
    ];
    $form['openweathermap']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('openweathermap.api_key'),
      '#required' => TRUE,
      '#description' => $this->t('You can get API key on account page in the <a href="@link">Open Weather Map</a>.', ['@link' => 'https://openweathermap.org/']),
    ];
    $period = [
      0,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $period = array_map([
      $this->dateFormatter,
      'formatInterval',
    ], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';
    $form['openweathermap']['cache_maximum_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache maximum age'),
      '#default_value' => $config->get('openweathermap.cache_maximum_age'),
      '#options' => $period,
      '#description' => $this->t('The maximum age for which weather info is cached before checking for new updates.'),
    ];

    $form['default'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default Settings'),
      '#tree' => TRUE,
    ];
    $form['default']['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback Location'),
      '#description' => $this->t('For enabling autocomplete you should add required credentials for geolocation service.'),
      '#autocomplete_route_name' => 'myportal_weather.location_autocomplete',
      '#default_value' => $config->get('default.location.name'),
    ];

    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping Settings'),
      '#tree' => TRUE,
    ];

    $location_hdp_mapping = $config->get('location_hdp_mapping');
    $form['mapping']['location_hdp_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Location HDP Mapping'),
      '#description' => $this->t("Enter one value per line, in the format 'Location HDP|Real Location'."),
      '#default_value' => $this->array2text($location_hdp_mapping),
      '#rows' => 10,
    ];

    $country_location_mapping = $config->get('country_location_mapping');
    $form['mapping']['country_location_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Country Location Mapping'),
      '#description' => $this->t("Enter one value per line, in the format 'Country|Real Location'."),
      '#default_value' => $this->array2text($country_location_mapping),
      '#rows' => 10,
    ];

    $form['mapping']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Print debug location mapping found'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Convert array to text (for textarea).
   *
   * @param array|null $values
   *   The array values.
   *
   * @return string
   *   The text converted.
   */
  protected function array2text($values) {
    if (!is_array($values)) {
      return '';
    }

    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }

    return implode("\n", $lines);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate the default location (if submitted).
    $this->validateDefaultLocation($form, $form_state);

    // Check other locations and show warning if not found.
    $debug = $form_state->getValue(['mapping', 'debug']);
    $debug_info = [];

    foreach (['location_hdp_mapping', 'country_location_mapping'] as $field) {

      $country_location_mapping = $form_state->getValue(['mapping', $field]);
      foreach ($this->text2array($country_location_mapping) as $input) {

        // Check location.
        $found_location = $this->checkLocation($input);

        if ($found_location === NULL) {
          $this->messenger()
            ->addWarning($this->t("Location %input not found for settings %settings_name.", [
              '%input' => $input,
              '%settings_name' => $form['mapping'][$field]['#title'],
            ]));
        }
        elseif ($debug && $found_location instanceof LocationInterface) {
          // Store in array the debug information.
          $debug_info[] = "{$input}: " . json_encode($found_location->toArray());
        }
      }
    }

    if (!empty($debug_info)) {
      // Print the debug information.
      $this->messenger()
        ->addStatus(Markup::create(implode('<br>', $debug_info)));
    }
  }

  /**
   * Validate the default location.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateDefaultLocation(array &$form, FormStateInterface $form_state) {
    $input_location = $form_state->getValue(['default', 'location']);
    if (empty($input_location)) {
      return;
    }

    $location = $this->checkLocation($input_location);
    if ($location instanceof LocationInterface) {
      $form_state->set('location', $location);
    }
    else {
      $form_state->setErrorByName('default][location', "Could not find the coordinates for location inserted. Please try again.");
    }
  }

  /**
   * Try geocoding the input.
   *
   * @param string $input_location
   *   The input text.
   *
   * @return \Drupal\myportal_weather\LocationInterface|null
   *   The location found. NULL if not found.
   */
  protected function checkLocation($input_location): ?LocationInterface {
    $locations = $this->geocodingProvider->getCoordinatesByLocationName($input_location);

    return !empty($locations) ? reset($locations) : NULL;
  }

  /**
   * Convert the text (of textarea) to array.
   *
   * @param string $string
   *   The text.
   *
   * @return array
   *   The array values.
   */
  protected function text2array($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');
    foreach ($list as $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $values[$key] = $value;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $config_factory = $container->get('config.factory');
    assert($config_factory instanceof ConfigFactoryInterface);
    $instance->configFactory = $config_factory;

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountProxyInterface);
    $instance->currentUser = $current_user;

    $geocoding_provider = $container->get('myportal_weather.geocoding.openweathermap');
    assert($geocoding_provider instanceof GeocodingProviderInterface);
    $instance->geocodingProvider = $geocoding_provider;

    $date_formatter = $container->get('date.formatter');
    assert($date_formatter instanceof DateFormatterInterface);
    $instance->dateFormatter = $date_formatter;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('myportal_weather.settings');
    $values_openweathermap = $form_state->getValue('openweathermap');
    $values_mapping = $form_state->getValue('mapping');
    $config
      ->set('openweathermap.api_key', $values_openweathermap['api_key'])
      ->set('openweathermap.cache_maximum_age', $values_openweathermap['cache_maximum_age'])
      ->set('location_hdp_mapping', $this->text2array($values_mapping['location_hdp_mapping']))
      ->set('country_location_mapping', $this->text2array($values_mapping['country_location_mapping']));

    if ($form_state->has('location')) {
      /** @var \Drupal\myportal_weather\LocationInterface $location */
      $location = $form_state->get('location');
      $config->set('default.location', $location->toArray());
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'myportal_weather.settings',
    ];
  }

}
