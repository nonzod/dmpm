<?php

namespace Drupal\myportal_weather\OWM;

use Drupal\myportal_weather\OWM\Resources\Resource;

/**
 * Defines the Factory class.
 *
 * @method \Drupal\myportal_weather\OWM\Resources\Geocoding       geocoding()
 * @method \Drupal\myportal_weather\OWM\Resources\CurrentWeather  currentWeather()
 *
 * @package Drupal\myportal_weather\OWM
 */
class Factory {

  /**
   * The client.
   *
   * @var \Drupal\myportal_weather\OWM\Client
   */
  protected $client;

  /**
   * Construct new Factory instance.
   *
   * @param array $config
   *   An array of configurations. You need at least the 'api_key'.
   * @param null|Client $client
   *   The Http Client (Defaults to Guzzle).
   * @param array $client_options
   *   Options to be passed to Guzzle upon each request.
   * @param bool $wrap_response
   *   Wrap request response in own Response object.
   */
  final public function __construct(array $config = [], ?Client $client = NULL, array $client_options = [], bool $wrap_response = TRUE) {
    if (is_null($client)) {
      $client = new Client($config, NULL, $client_options, $wrap_response);
    }
    $this->client = $client;
  }

  /**
   * Return an instance of a Resource based on the method called.
   *
   * @param string $name
   *   The name function.
   * @param mixed $args
   *   The arguments.
   *
   * @return \Drupal\myportal_weather\OWM\Resources\Resource
   *   The resource request.
   *
   * @psalm-suppress MoreSpecificReturnType
   * @psalm-suppress InvalidStringClass
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function __call(string $name, $args): Resource {
    $resource = 'Drupal\\myportal_weather\\OWM\\Resources\\' . ucfirst($name);

    return new $resource($this->client, ...$args);
  }

  /**
   * Retrieve the client.
   *
   * @return Client
   *   The client.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Create an instance of the service with an API key.
   *
   * @param string|null $api_key
   *   OpenWeatherMap API key.
   * @param \Drupal\myportal_weather\OWM\Client|null $client
   *   The Http client.
   * @param array $client_options
   *   Options to be send with each request.
   * @param bool $wrap_response
   *   Wrap request response in own Response object.
   *
   * @return static
   *   The service.
   */
  public static function create(string $api_key = NULL, ?Client $client = NULL, array $client_options = [], bool $wrap_response = TRUE): self {
    return new static(['api_key' => $api_key], $client, $client_options, $wrap_response);
  }

}
