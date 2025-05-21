<?php

namespace Drupal\myportal_weather\OWM;

use Drupal\myportal_weather\WeatherInterface;

/**
 * Defines the Weather class.
 *
 * @package Drupal\myportal_weather
 */
class Weather implements \ArrayAccess, WeatherInterface {

  /**
   * The array storage.
   *
   * @var array
   */
  protected $array = [];

  /**
   * Construct new Weather instance.
   *
   * @param array $array
   *   The array data.
   */
  final public function __construct(array $array) {
    $this->array = $array;
  }

  /**
   * Create weather from array data response.
   *
   * @param mixed $data
   *   The data input.
   *
   * @return static
   *   The object weather.
   */
  public static function fromResponse($data) {
    $array = [];

    // Required.
    $array['name'] = $data->name;
    $array['units'] = $data->units;
    $array['lang'] = $data->lang;

    // Base weather.
    $weather = $data->weather['0'];
    $array['icon'] = "https://openweathermap.org/img/wn/{$weather->icon}@2x.png";
    $array['description'] = $weather->description;

    // Main.
    $main = $data->main;
    $array['temp'] = $main->temp;
    $array['humidity'] = $main->humidity;

    // Rain.
    $array['rain'] = isset($data->rain->{'1h'}) ?? NULL;

    // Wind.
    $array['wind'] = $data->wind->speed;

    // Clouds.
    $array['clouds'] = isset($data->clouds->all) ?? NULL;

    return new static($array);
  }

  /**
   * {@inheritDoc}
   */
  public function offsetExists($index) {
    return isset($this->array[$index]);
  }

  /**
   * {@inheritDoc}
   */
  public function offsetGet($index) {
    if ($this->offsetExists($index)) {
      return $this->array[$index];
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function offsetSet($index, $value) {
    if ($index) {
      $this->array[$index] = $value;
    }
    else {
      $this->array[] = $value;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function offsetUnset($index) {
    unset($this->array[$index]);
  }

  /**
   * Return array.
   *
   * @return array
   *   The array storage.
   */
  public function toArray() {
    return $this->array;
  }

  /**
   * {@inheritDoc}
   */
  public function getName() {
    return $this->offsetGet('name');
  }

  /**
   * {@inheritDoc}
   */
  public function getLang() {
    return $this->offsetGet('lang');
  }

  /**
   * {@inheritDoc}
   */
  public function getUnits() {
    return $this->offsetGet('units');
  }

  /**
   * {@inheritDoc}
   */
  public function getIcon() {
    return $this->offsetGet('icon');
  }

  /**
   * {@inheritDoc}
   */
  public function getTemp() {
    return $this->offsetGet('temp');
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription() {
    return $this->offsetGet('description');
  }

  /**
   * {@inheritDoc}
   */
  public function getHumidity() {
    return $this->offsetGet('humidity');
  }

  /**
   * {@inheritDoc}
   */
  public function getRain() {
    return $this->offsetGet('rain');
  }

  /**
   * {@inheritDoc}
   */
  public function getWind() {
    return $this->offsetGet('wind');
  }

  /**
   * {@inheritDoc}
   */
  public function getClouds() {
    return $this->offsetGet('clouds');
  }

}
