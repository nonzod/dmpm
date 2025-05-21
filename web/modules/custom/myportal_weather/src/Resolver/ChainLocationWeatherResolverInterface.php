<?php

namespace Drupal\myportal_weather\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the location.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the base resolver one.
 *
 * @package Drupal\myportal_weather\Resolver
 */
interface ChainLocationWeatherResolverInterface extends LocationWeatherResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\myportal_weather\Resolver\LocationWeatherResolverInterface $resolver
   *   The resolvers.
   */
  public function addResolver(LocationWeatherResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\myportal_weather\Resolver\LocationWeatherResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers(): array;

}
