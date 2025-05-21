<?php

namespace Drupal\myportal_weather\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface;
use Drupal\myportal_weather\Service\Weather\WeatherProviderInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the AjaxWeatherController class.
 *
 * @package Drupal\myportal_weather\Controller
 */
class AjaxWeatherController extends ControllerBase {

  /**
   * The geocoding service.
   *
   * @var \Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface
   */
  protected $geocodingService;

  /**
   * The weather service.
   *
   * @var \Drupal\myportal_weather\Service\Weather\WeatherProviderInterface
   */
  protected $weatherProvider;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $geocoding_service = $container->get('myportal_weather.geocoding.openweathermap');
    assert($geocoding_service instanceof GeocodingProviderInterface);
    $instance->geocodingService = $geocoding_service;

    $user_data = $container->get('user.data');
    assert($user_data instanceof UserDataInterface);
    $instance->userData = $user_data;

    $weather_provider = $container->get('myportal_weather.weather.openweathermap');
    assert($weather_provider instanceof WeatherProviderInterface);
    $instance->weatherProvider = $weather_provider;

    $renderer = $container->get('renderer');
    assert($renderer instanceof RendererInterface);
    $instance->renderer = $renderer;

    return $instance;
  }

  /**
   * Retrieve the widget weather.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   *
   * @throws \Exception
   */
  public function widget(Request $request) {
    $response = new AjaxResponse();

    $location_name = $request->query->get('lname');
    if (empty($location_name)) {
      $response->addCommand(new ReplaceCommand('.weather-widget', 'Not found location!'));

      return $response;
    }

    $units = $this->userData->get('myportal_weather', $this->currentUser()
      ->id(), 'degrees');
    $context = [
      'user_id' => $this->currentUser->id(),
      'units' => in_array($units, ['imperial', 'metric']) ? $units : 'metric',
      'lang' => $this->languageManager()->getCurrentLanguage()->getId(),
    ];

    $locations = $this->geocodingService->getCoordinatesByLocationName($location_name);
    if (empty($locations)) {
      $response->addCommand(new ReplaceCommand('.weather-widget', 'Not found coordinates of location!'));

      return $response;
    }

    $location = reset($locations);

    $weather_widget = [
      '#theme' => 'myportal_weather_widget',
      '#weather' => $this->weatherProvider->getCurrentWeatherInfo($location, $context),
      '#location' => $location,
      '#context' => $context,
      '#cache' => [
        'context' => ['user'],
        'max-age' => 60 * 60,
      ],
      '#attributes' => ['class' => ['open']],
    ];

    $response->addCommand(new ReplaceCommand('.weather-widget', $this->renderer->render($weather_widget)));

    return $response;
  }

}
