<?php

namespace Drupal\myportal_weather;

/**
 * Defines the LocationInterface trait.
 *
 * @package Drupal\myportal_weather
 */
interface LocationInterface {

  /**
   * Create location object from array data.
   *
   * @param array $array
   *   The array contains names, lat and lon.
   *
   * @return static
   *   The object location.
   */
  public static function fromArray(array $array);

  /**
   * Retrieve the coordinates.
   *
   * @return null|string
   *   Null if not set.
   */
  public function getLat(): ?string;

  /**
   * Retrieve the coordinates.
   *
   * @return null|string
   *   Null if not set.
   */
  public function getLon(): ?string;

  /**
   * Retrieve the name.
   *
   * @return string
   *   The location name.
   */
  public function getName(): string;

  /**
   * Return an array of data.
   *
   * @return array
   *   The data object.
   */
  public function toArray();

}
