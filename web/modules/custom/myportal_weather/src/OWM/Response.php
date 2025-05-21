<?php

namespace Drupal\myportal_weather\OWM;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Defines the Response class.
 *
 * @package Drupal\myportal_weather\OWM
 */
class Response implements ResponseInterface, \ArrayAccess {

  /**
   * The data response.
   *
   * @var mixed
   */
  public $data;

  /**
   * The original response.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * Construct new Response instance.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   THe response object.
   */
  public function __construct(ResponseInterface $response) {
    $this->response = $response;
    $this->data = $this->getDataFromResponse($response);
  }

  /**
   * Get the api data from the response as usual.
   *
   * @param string $name
   *   The name.
   *
   * @return mixed
   *   The value requested.
   */
  public function __get($name) {
    return $this->data->{$name};
  }

  /**
   * Get the underlying data.
   *
   * @return mixed
   *   The data of response.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Return an array of the data.
   *
   * @return array
   *   An array of data
   */
  public function toArray() {
    return json_decode(json_encode($this->data), TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function offsetExists($offset) {
    return isset($this->data->{$offset});
  }

  /**
   * {@inheritDoc}
   */
  public function offsetGet($offset) {
    $data = $this->toArray();

    return $data[$offset];
  }

  /**
   * {@inheritDoc}
   */
  public function offsetSet($offset, $value) {
    $this->data->{$offset} = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function offsetUnset($offset) {
    unset($this->data->{$offset});
  }

  /**
   * {@inheritDoc}
   */
  public function getProtocolVersion() {
    return $this->response->getProtocolVersion();
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function withProtocolVersion($version) {
    // @phpstan-ignore-next-line
    return $this->response->withProtocolVersion($version);
  }

  /**
   * {@inheritDoc}
   */
  public function getHeaders() {
    return $this->response->getHeaders();
  }

  /**
   * {@inheritDoc}
   */
  public function hasHeader($name) {
    return $this->response->hasHeader($name);
  }

  /**
   * {@inheritDoc}
   */
  public function getHeader($name) {
    return $this->response->getHeader($name);
  }

  /**
   * {@inheritDoc}
   */
  public function getHeaderLine($name) {
    return $this->response->getHeaderLine($name);
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function withHeader($name, $value) {
    // @phpstan-ignore-next-line
    return $this->response->withHeader($name, $value);
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function withAddedHeader($name, $value) {
    // @phpstan-ignore-next-line
    return $this->response->withAddedHeader($name, $value);
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function withoutHeader($name) {
    // @phpstan-ignore-next-line
    return $this->response->withoutHeader($name);
  }

  /**
   * {@inheritDoc}
   */
  public function getBody() {
    return $this->response->getBody();
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function withBody(StreamInterface $body) {
    // @phpstan-ignore-next-line
    return $this->response->withBody($body);
  }

  /**
   * {@inheritDoc}
   */
  public function getStatusCode() {
    return $this->response->getStatusCode();
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress LessSpecificReturnStatement
   */
  public function withStatus($code, $reasonPhrase = '') {
    // @phpstan-ignore-next-line
    return $this->response->withStatus($code, $reasonPhrase);
  }

  /**
   * {@inheritDoc}
   */
  public function getReasonPhrase() {
    return $this->response->getReasonPhrase();
  }

  /**
   * Retrieve data from response.
   *
   * @return mixed
   *   The response data content.
   */
  private function getDataFromResponse(ResponseInterface $response) {
    $contents = $response->getBody()->getContents();

    return $contents ? json_decode($contents) : NULL;
  }

}
