<?php

namespace Drupal\myportal_weather\OWM\Resources;

use Drupal\myportal_weather\OWM\Client;

/**
 * Defines the Resource class.
 *
 * @package Drupal\myportal_weather\OWM\Resources
 */
abstract class Resource {

  /**
   * The client.
   *
   * @var \Drupal\myportal_weather\OWM\Client
   */
  protected $client;

  /**
   * Construct new Resource instance.
   *
   * @param \Drupal\myportal_weather\OWM\Client $client
   *   The OpenWeatherMap client.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

}
