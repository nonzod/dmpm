<?php

namespace Drupal\myportal_weather\Service\Weather;

use Drupal\myportal_weather\LocationInterface;

/**
 * Defines the WeatherProviderInterface trait.
 *
 * @package Drupal\myportal_weather\Service\Weather
 */
interface WeatherProviderInterface {

  /**
   * Get current weather data.
   *
   * @param \Drupal\myportal_weather\LocationInterface $location
   *   The target location.
   * @param array $context
   *   The context.
   *
   * @return \Drupal\myportal_weather\WeatherInterface|null
   *   The data of weather. NULL if not found data.
   */
  public function getCurrentWeatherInfo(LocationInterface $location, array $context);

}
