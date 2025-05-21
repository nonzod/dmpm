<?php

namespace Drupal\myportal_weather\Resolver;

use Drupal\myportal_weather\LocationInterface;

/**
 * Defines for the location weather resolvers.
 *
 * @package Drupal\myportal_weather\Resolver
 */
interface LocationWeatherResolverInterface {

  /**
   * Resolves the location for weather.
   *
   * @param array $context
   *   The context.
   *
   * @return null|\Drupal\myportal_weather\LocationInterface
   *   The target location.
   */
  public function resolve(array $context): ?LocationInterface;

}
