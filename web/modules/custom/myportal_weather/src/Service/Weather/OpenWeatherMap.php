<?php

namespace Drupal\myportal_weather\Service\Weather;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\myportal_weather\LocationInterface;
use Drupal\myportal_weather\OWM\Factory;
use Drupal\myportal_weather\OWM\Weather;

/**
 * Defines the OpenWeatherMap service.
 *
 * @package Drupal\myportal_weather\Service\Weather
 */
class OpenWeatherMap implements WeatherProviderInterface {

  use LoggerChannelTrait;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The myportal_weather.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The target resource.
   *
   * @var \Drupal\myportal_weather\OWM\Resources\CurrentWeather
   */
  protected $owmCurrentWeather;

  /**
   * Construct new OpenWeatherMap instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->config = $config_factory->get('myportal_weather.settings');
    $this->owmCurrentWeather = (new Factory([
      'api_key' => $this->config->get('openweathermap.api_key'),
    ]))->currentWeather();
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentWeatherInfo(LocationInterface $location, array $context) {
    // Calculate hash request.
    $cid = hash('sha256', serialize($location->toArray() + ['units' => $context['units']] + ['lang' => $context['lang']]));
    $expire_time_cache = $this->config->get('openweathermap.cache_maximum_age');

    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      try {
        // Retrieve the weather info.
        $data = $this->getWeatherInfo($location, $context);
      }
      catch (\Exception $exception) {
        $this->getLogger('myportal_weather')->error($exception->getMessage());
        $expire_time_cache = time() + 300;
        $data = NULL;
      }
      $this->cache->set($cid, $data, time() + $expire_time_cache);
    }

    return $data;
  }

  /**
   * Retrieve the weather info.
   *
   * @param \Drupal\myportal_weather\LocationInterface $location
   *   The target location.
   * @param array $context
   *   The context.
   *
   * @return \Drupal\myportal_weather\OWM\Weather
   *   The weather info.
   *
   * @throws \Drupal\myportal_weather\OWM\Exceptions\Exception
   */
  protected function getWeatherInfo(LocationInterface $location, array $context) {
    switch (TRUE) {
      case !empty($location->getLat()) && !empty($location->getLon()):
        $response = $this->owmCurrentWeather->retrieveByCoordinates(
          (string) $location->getLat(),
          (string) $location->getLon(),
          $context['units'] ?? 'metrics',
          $context['lang'] ?? 'en',
        );

        $response_data = $response->getStatusCode() == 200 ? $response->getData() : [];

        return Weather::fromResponse($response_data);

      case !empty($location->getName()):
        $response = $this->owmCurrentWeather->retrieveByLocationName(
          $location->getName(),
          $context['units'] ?? 'metrics',
          $context['lang'] ?? 'en',
        );

        $response_data = $response->getStatusCode() == 200 ? $response->getData() : [];

        return Weather::fromResponse($response_data);

      default:
        throw new \InvalidArgumentException('Location not valid.');
    }
  }

}
