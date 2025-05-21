<?php

namespace Drupal\myportal_weather;

/**
 * Defines the WeatherInterface trait.
 *
 * @package Drupal\myportal_weather
 */
interface WeatherInterface {

  /**
   * Create weather from array data.
   *
   * @param mixed $data
   *   The data.
   *
   * @return static
   *   The object weather.
   */
  public static function fromResponse($data);

  /**
   * Retrieve the clouds (%).
   *
   * @return string
   *   The clouds value.
   */
  public function getClouds();

  /**
   * Retrieve the weather description.
   *
   * @return string
   *   The weather description.
   */
  public function getDescription();

  /**
   * Retrieve the humidity.
   *
   * @return string
   *   The humidity value.
   */
  public function getHumidity();

  /**
   * Retrieve the (url) icon.
   *
   * @return string
   *   The icon.
   */
  public function getIcon();

  /**
   * Retrieve the language code.
   *
   * @return string|null
   *   The language code.
   */
  public function getLang();

  /**
   * Retrieve the location name.
   *
   * @return string
   *   The location name.
   */
  public function getName();

  /**
   * Retrieve the rain.
   *
   * @return mixed
   *   The rain value.
   */
  public function getRain();

  /**
   * Retrieve the temperature.
   *
   * @return string|int
   *   The temp.
   */
  public function getTemp();

  /**
   * Retrieve the units used.
   *
   * @return string
   *   The units: imperial or metrics.
   */
  public function getUnits();

  /**
   * Retrieve the wind.
   *
   * @return string
   *   The wind value.
   */
  public function getWind();

}
