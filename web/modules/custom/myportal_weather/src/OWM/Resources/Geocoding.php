<?php

namespace Drupal\myportal_weather\OWM\Resources;

use Drupal\myportal_weather\OWM\Response;

/**
 * Defines the Geocoding Resource class.
 *
 * @package Drupal\myportal_weather\OWM\Resources
 * @see https://openweathermap.org/api/geocoding-api
 */
class Geocoding extends Resource {

  /**
   * Retrieve Coordinates by location name.
   *
   * @param string $search
   *   City name, state code (only for US) and country code divided by comma.
   *   Please use ISO 3166 country codes.
   * @param int $limit
   *   Number of the locations returned.
   *
   * @return \Drupal\myportal_weather\OWM\Response
   *   The response.
   *
   * @throws \Drupal\myportal_weather\OWM\Exceptions\Exception
   *
   * @see https://openweathermap.org/api/geocoding-api#direct
   */
  public function getCoordinatesByLocationName(string $search, int $limit = 5) {
    $endpoint = "http://api.openweathermap.org/geo/1.0/direct";

    $response = $this->client->request(
      'get',
      $endpoint,
      [],
      ['q' => $search, 'limit' => $limit]
    );
    assert($response instanceof Response);

    return $response;
  }

  /**
   * Reverse Geocoding.
   *
   * @param string $lat
   *   Geographical coordinates (latitude, longitude).
   * @param string $lon
   *   Geographical coordinates (latitude, longitude).
   * @param int $limit
   *   Number of the locations returned.
   *
   * @return \Drupal\myportal_weather\OWM\Response
   *   The response.
   *
   * @throws \Drupal\myportal_weather\OWM\Exceptions\Exception
   *
   * @see https://openweathermap.org/api/geocoding-api#reverse
   */
  public function reverseGeocoding(string $lat, string $lon, int $limit = 5) {
    $endpoint = "http://api.openweathermap.org/geo/1.0/reverse";

    $response = $this->client->request(
      'get',
      $endpoint,
      [],
      ['lat' => $lat, 'lon' => $lon, 'limit' => $limit]
    );
    assert($response instanceof Response);

    return $response;
  }

}
