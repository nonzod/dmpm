<?php

declare(strict_types=1);

namespace Drupal\myaccess\OpenId;

use GuzzleHttp\MessageFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SocialConnect\HttpClient\Curl;

/**
 * Extend Curl to trace requests.
 */
class TraceableCurl extends Curl {

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * {@inheritDoc}
   */
  public function sendRequest(RequestInterface $request): ResponseInterface {
    $now = time();
    $response = parent::sendRequest($request);

    if ($this->logger == NULL) {
      return $response;
    }

    $this->logger->log($this->getLogLevel($response), "@message ended after @time seconds.", [
      '@message' => $this->getDefaultFormatter()->format($request, $response),
      '@time' => time() - $now,
    ]);

    // Make sure that the content of the body is available again.
    $response->getBody()->seek(0);

    return $response;
  }

  /**
   * Set the logger.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Returns the default formatter.
   *
   * @return \GuzzleHttp\MessageFormatter
   *   The message formatter.
   */
  protected function getDefaultFormatter() {
    return new MessageFormatter(_myaccess_get_template_message_formatter());
  }

  /**
   * Returns a log level for a given response.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response being logged.
   *
   * @return string
   *   LogLevel.
   */
  protected function getLogLevel(ResponseInterface $response = NULL) {
    if ($response && $response->getStatusCode() >= 300) {
      return LogLevel::NOTICE;
    }

    return LogLevel::INFO;
  }

}
