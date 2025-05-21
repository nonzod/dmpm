<?php

namespace Drupal\myportal_weather\OWM;

use Drupal\myportal_weather\OWM\Exceptions\BadRequest;
use Drupal\myportal_weather\OWM\Exceptions\Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

/**
 * Defines the OpenWeatherMap Client class.
 *
 * @package Drupal\myportal_weather\OWM
 */
class Client {

  /**
   * The API Key.
   *
   * @var string
   */
  public $apiKey;

  /**
   * The client.
   *
   * @var \GuzzleHttp\Client
   */
  protected GuzzleClient $client;

  /**
   * Guzzle allows options into its request method. Prepare for some defaults.
   *
   * @var array
   */
  protected $clientOptions = [];

  /**
   * Wrap response.
   *
   * If set to false, no Response object is created, but the one from Guzzle
   * is directly returned comes in handy own error handling.
   *
   * @var bool
   */
  protected $wrapResponse = TRUE;

  /**
   * Construct new OpenWeatherMap Client instance.
   *
   * @param array $config
   *   Configuration array.
   * @param null|GuzzleClient $client
   *   The Http Client (Defaults to Guzzle).
   * @param array $client_options
   *   Options to be passed to Guzzle upon each request.
   * @param bool $wrap_response
   *   Wrap request response in own Response object.
   */
  public function __construct(array $config = [], ?GuzzleClient $client = NULL, array $client_options = [], bool $wrap_response = TRUE) {
    $this->clientOptions = $client_options;
    $this->wrapResponse = $wrap_response;
    $this->apiKey = $config['api_key'] ?? getenv('OPENWEATHERMAP_APIKEY');

    if (is_null($client)) {
      $client = new GuzzleClient();
    }
    $this->client = $client;
  }

  /**
   * Send the request.
   *
   * @param string $method
   *   The HTTP request verb.
   * @param string $endpoint
   *   The OpenWeatherMap API endpoint.
   * @param array $options
   *   An array of options to send with the request.
   * @param array $query_params
   *   A query array to send with the request.
   *
   * @return \Drupal\myportal_weather\OWM\Response|\Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @throws \Drupal\myportal_weather\OWM\Exceptions\Exception
   */
  public function request(string $method, string $endpoint, array $options = [], array $query_params = []) {

    if (empty($this->apiKey)) {
      throw new Exception('You must provide a OpenWeather api key.');
    }

    $url = $this->generateUrl($endpoint, $query_params);
    $options = array_merge($this->clientOptions, $options);

    try {
      if (FALSE === $this->wrapResponse) {
        return $this->client->request($method, $url, $options);
      }

      return new Response($this->client->request($method, $url, $options));
    }
    catch (ClientException $e) {
      throw BadRequest::create($e);
    }
    catch (GuzzleException | ServerException $e) {
      throw Exception::create($e);
    }
  }

  /**
   * Generate the full endpoint url, including query string.
   *
   * @param string $endpoint
   *   The OpenWeather API endpoint.
   * @param array $query_params
   *   The query params to send to the endpoint.
   *
   * @return string
   *   The complete url.
   */
  protected function generateUrl(string $endpoint, array $query_params = []) {
    $query_params['appid'] = $this->apiKey;

    return $endpoint . '?' . http_build_query($query_params);
  }

}
