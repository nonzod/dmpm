<?php

namespace Drupal\myportal_weather\OWM\Exceptions;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines the OpenWeatherMap Exception class.
 *
 * @package Drupal\myportal_weather\OWM\Exceptions
 */
class Exception extends \Exception {

  /**
   * The request.
   *
   * @var null|\Psr\Http\Message\RequestInterface
   */
  protected $request;

  /**
   * The response.
   *
   * @var null|\Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * Create the exception.
   *
   * @param \GuzzleHttp\Exception\GuzzleException $guzzleException
   *   The exception.
   *
   * @return static
   *   The itself class.
   *
   * @psalm-suppress UnsafeInstantiation
   */
  public static function create(GuzzleException $guzzleException): self {

    $message = static::sanitizeResponseMessage(
      $guzzleException instanceof RequestException ?
        $guzzleException->getRequest()->getUri() : ''
    );
    $message .= ' >> ' . static::sanitizeResponseMessage($guzzleException->getMessage());

    $new_exception = new static($message, (int) $guzzleException->getCode());

    if ($guzzleException instanceof RequestException) {
      $new_exception->response = $guzzleException->getResponse();
      $new_exception->request = $guzzleException->getRequest();
    }

    return $new_exception;
  }

  /**
   * Sanitize response for remove sensitive data.
   *
   * @param string $message
   *   The message.
   *
   * @return string
   *   The message.
   */
  protected static function sanitizeResponseMessage(string $message): string {
    return preg_replace('/(appid)=[a-z0-9-]+/i', '$1=***', $message);
  }

  /**
   * Retrieve the original request.
   *
   * @return null|\Psr\Http\Message\RequestInterface
   *   The request.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Retrieve the original response.
   *
   * @return null|\Psr\Http\Message\ResponseInterface
   *   The response.
   */
  public function getResponse() {
    return $this->response;
  }

}
