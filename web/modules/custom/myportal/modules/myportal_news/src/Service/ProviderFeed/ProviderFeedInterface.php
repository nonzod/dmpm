<?php

namespace Drupal\myportal_news\Service\ProviderFeed;

/**
 * Defines the ProviderFeedInterface trait.
 *
 * @package Drupal\myportal_news\Service\ProviderFeed
 */
interface ProviderFeedInterface {

  /**
   * Fetch the data from provider.
   *
   * @param string $url
   *   The url source.
   * @param array $parameters
   *   An array with extra parameters.
   *
   * @return mixed
   *   The raw data.
   */
  public function fetch(string $url, array $parameters);

  /**
   * Convert the raw data to NewsFeedItem object.
   *
   * @param mixed $source
   *   The raw data.
   * @param array $parameters
   *   An array with extra parameters.
   *
   * @return \Drupal\myportal_news\Model\News[]
   *   An array of object.
   */
  public function parse($source, array $parameters): array;

}
