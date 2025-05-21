<?php

namespace Drupal\myportal_news\Service\ProviderFeed;

use Drupal\myportal_news\Model\News;
use GuzzleHttp\ClientInterface;

/**
 * Defines the LaPresseIt class.
 *
 * @package Drupal\myportal_news\Service\ProviderFeed
 */
class LaPresseItService implements ProviderFeedInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Construct new LaPresseIt instance.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritDoc}
   */
  public function fetch(string $url, array $parameters) {
    // Request.
    $response = $this->httpClient->request('GET', $url);

    // Decode data.
    return json_decode((string) $response->getBody());
  }

  /**
   * {@inheritDoc}
   */
  public function parse($source, array $parameters): array {

    $items = [];
    foreach ($source as $item) {
      $items[] = new News(
        $item->title->rendered,
        $item->date_gmt,
        $item->excerpt->rendered,
        $item->content->rendered,
        $item->link
      );
    }

    return $items;
  }

}
