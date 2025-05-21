<?php

namespace Drupal\myportal_weather\OWM;

use Drupal\myportal_weather\LocationInterface;

/**
 * Defines the Location class.
 *
 * @package Drupal\myportal_weather\OWM
 */
class Location implements LocationInterface {

  /**
   * Location lat.
   *
   * @var null|string
   */
  protected ?string $lat;

  /**
   * Location lon.
   *
   * @var null|string
   */
  protected ?string $lon;

  /**
   * Location name.
   *
   * @var string
   */
  protected string $name;

  /**
   * Construct new Location instance.
   *
   * @param string $name
   *   The name location.
   * @param null|string $lat
   *   The lat.
   * @param null|string $lon
   *   The lon.
   */
  final public function __construct(string $name, ?string $lat, ?string $lon) {
    $this->name = $name;
    $this->lat = $lat;
    $this->lon = $lon;
  }

  /**
   * {@inheritDoc}
   */
  public static function fromArray(array $array) {
    assert(isset($array['name']), 'Location object required the field "name".');
    assert(isset($array['lat']), 'Location object required the field "lat".');
    assert(isset($array['lon']), 'Location object required the field "lon".');

    return new static($array['name'], $array['lat'], $array['lon']);
  }

  /**
   * {@inheritDoc}
   */
  public function getLat(): ?string {
    return $this->lat;
  }

  /**
   * {@inheritDoc}
   */
  public function getLon(): ?string {
    return $this->lon;
  }

  /**
   * {@inheritDoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritDoc}
   */
  public function toArray() {
    return [
      'name' => $this->name,
      'lon' => $this->lon,
      'lat' => $this->lat,
    ];
  }

}
