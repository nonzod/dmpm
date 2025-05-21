<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Provides a function to build uncacheable TrustedRedirectResponses.
 */
trait UncacheableRedirectTrait {

  /**
   * Return an uncacheable TrustedRedirectResponse.
   *
   * @param string $url
   *   The url to redirect to.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   An uncacheable TrustedRedirectResponse.
   */
  public function buildUncacheableRedirect(string $url): TrustedRedirectResponse {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $response = new TrustedRedirectResponse($url);
    $cache_metadata = $this->buildNonCacheableMetadata();
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

  /**
   * Build a CacheableMetadata that doesn't cache anything.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   A CacheableMetadata that doesn't cache anything.
   */
  private function buildNonCacheableMetadata(): CacheableMetadata {
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return CacheableMetadata::createFromRenderArray($build);
  }

}
