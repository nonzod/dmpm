<?php

namespace Drupal\myportal_weather\Service\Geocoding;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\myportal_weather\OWM\Factory;
use Drupal\myportal_weather\OWM\Location;

/**
 * Defines the OpenWeatherMap class.
 *
 * @package Drupal\myportal_weather\Service\Geocoding
 */
class OpenWeatherMap implements GeocodingProviderInterface {

  use LoggerChannelTrait;

  const CACHE_TIME = 60 * 60 * 3;

  /**
   * The target resource.
   *
   * @var \Drupal\myportal_weather\OWM\Resources\Geocoding
   */
  protected $owmGeocoding;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Construct new OpenWeatherMap instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = [
      'api_key' => $config_factory->get('myportal_weather.settings')
        ->get('openweathermap.api_key'),
    ];
    $this->owmGeocoding = (new Factory($config))->geocoding();
  }

  /**
   * {@inheritDoc}
   */
  public function getCoordinatesByLocationName(string $text, int $limit = 5) {
    try {
      $response = $this->owmGeocoding->getCoordinatesByLocationName($text, $limit);

      if ($response->getStatusCode() != 200) {
        return [];
      }

      $locations = [];
      foreach ($response->getData() as $data) {

        // Build the name.
        $name = [$data->name, $data->state ?? NULL, $data->country ?? NULL];
        $name = array_filter($name);
        $name = implode(', ', $name);

        $locations[] = new Location($name, $data->lat ?? NULL, $data->lon ?? NULL);
      }

      return $locations;
    }
    catch (\Exception $exception) {
      $this->getLogger('myportal_weather')->error($exception->getMessage());
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getReverseGeocoding(string $lat, string $lon, int $limit = 1) {
    try {
      $response = $this->owmGeocoding->reverseGeocoding($lat, $lon, $limit);

      return $response->getStatusCode() == 200 ? $response->getData() : [];
    }
    catch (\Exception $exception) {
      $this->getLogger('myportal_weather')->error($exception->getMessage());
    }

    return [];
  }

}
