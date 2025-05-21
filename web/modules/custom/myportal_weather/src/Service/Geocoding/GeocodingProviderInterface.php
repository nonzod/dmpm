<?php

namespace Drupal\myportal_weather\Service\Geocoding;

/**
 * Defines the GeocodingProviderInterface trait.
 *
 * @package Drupal\myportal_weather\Service\Geocoding
 */
interface GeocodingProviderInterface {

  /**
   * Return the coordinates by location name.
   *
   * @param string $text
   *   City name, state code (only for US) and country code divided by comma.
   *   Please use ISO 3166 country codes.
   * @param int $limit
   *   Number of the locations returned.
   *
   * @return \Drupal\myportal_weather\LocationInterface[]
   *   An array of matching location.
   */
  public function getCoordinatesByLocationName(string $text, int $limit = 5);

  /**
   * Reverse geocoding data.
   *
   * @param string $lat
   *   Geographical coordinates (latitude, longitude)
   * @param string $lon
   *   Geographical coordinates (latitude, longitude)
   * @param int $limit
   *   Number of the location names returned.
   *
   * @return array
   *   An array of matching location.
   */
  public function getReverseGeocoding(string $lat, string $lon, int $limit = 1);

}
