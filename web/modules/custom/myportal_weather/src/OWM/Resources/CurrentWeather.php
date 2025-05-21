<?php

namespace Drupal\myportal_weather\OWM\Resources;

use Drupal\myportal_weather\OWM\Response;

/**
 * Defines the CurrentWeather Resource class.
 *
 * @package Drupal\myportal_weather\OWM\Resources
 * @see https://openweathermap.org/current
 */
class CurrentWeather extends Resource {

  /**
   * Retrieve the current weather by geographical coordinates.
   *
   * @param string $lat
   *   Geographical coordinates (latitude, longitude).
   * @param string $lon
   *   Geographical coordinates (latitude, longitude).
   * @param string $units
   *   Units of measurement. 'standard', 'metric' and 'imperial' units are
   *   available.
   * @param string $lang
   *   The output in language.
   *
   * @return \Drupal\myportal_weather\OWM\Response
   *   The response.
   *
   * @throws \Drupal\myportal_weather\OWM\Exceptions\Exception
   *
   * @see https://openweathermap.org/current#one
   */
  public function retrieveByCoordinates(string $lat, string $lon, string $units = 'standard', string $lang = 'en') {
    $endpoint = "https://api.openweathermap.org/data/2.5/weather";

    $response = $this->client->request(
      'get',
      $endpoint,
      [],
      ['lat' => $lat, 'lon' => $lon, 'units' => $units, 'lang' => $lang]
    );

    assert($response instanceof Response);
    if ($response->getStatusCode() == 200) {
      $response['units'] = $units;
      $response['lang'] = $lang;
    }

    return $response;
  }

  /**
   * Retrieve the current weather by geographical coordinates.
   *
   * @param string $text
   *   City name, state code and country code divided by comma.
   * @param string $units
   *   Units of measurement. 'standard', 'metric' and 'imperial' units are
   *   available.
   * @param string $lang
   *   The output in language.
   *
   * @return \Drupal\myportal_weather\OWM\Response
   *   The response.
   *
   * @throws \Drupal\myportal_weather\OWM\Exceptions\Exception
   *
   * @see https://openweathermap.org/current#name
   */
  public function retrieveByLocationName(string $text, string $units = 'standard', string $lang = 'en') {
    $endpoint = "https://api.openweathermap.org/data/2.5/weather";

    $response = $this->client->request(
      'get',
      $endpoint,
      [],
      ['q' => $text, 'units' => $units, 'lang' => $lang]
    );

    assert($response instanceof Response);
    if ($response->getStatusCode() == 200) {
      $response['units'] = $units;
      $response['lang'] = $lang;
    }

    return $response;
  }

}
