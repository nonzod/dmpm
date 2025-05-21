<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Cache\Cache;

/**
 * Expose a method to memoize functions.
 *
 * @see https://en.wikipedia.org/wiki/Memoization
 */
trait Memoize {

  /**
   * Memoize a function.
   *
   * @param callable $func
   *   The function to be memoized.
   * @param callable $keyGenerator
   *   The function used to generate the cache key.
   * @param array $tags
   *   An array of cache tags.
   * @param int $durationInSeconds
   *   How long this cache is valid.
   *
   * @return callable
   *   A new function that behaves as the input one but with output cached.
   */
  public function memoize(callable $func, callable $keyGenerator, array $tags = [], int $durationInSeconds = Cache::PERMANENT): callable {
    return function (...$args) use ($func, $keyGenerator, $tags, $durationInSeconds) {
      // Get the cache service.
      $cache = \Drupal::cache();

      // Compute cache expiration.
      if ($durationInSeconds == Cache::PERMANENT) {
        $expires = Cache::PERMANENT;
      }
      else {
        $expires = time() + $durationInSeconds;
      }

      // Compute the cache key.
      $key = $keyGenerator(...$args);

      // Lookup the key in the cache.
      $cached = $cache->get($key);

      if (is_bool($cached) && !$cached) {
        $data = $func(...$args);
        $cache->set($key, $data, $expires, $tags);

        return $data;
      }

      assert(is_object($cached));

      return $cached->data;
    };
  }

}
