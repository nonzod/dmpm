<?php

namespace Drupal\myportal_weather\Resolver;

use Drupal\myportal_weather\LocationInterface;

/**
 * Defines the ChainLocationWeatherResolver class.
 *
 * @package Drupal\myportal_weather\Resolver
 */
class ChainLocationWeatherResolver implements ChainLocationWeatherResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\myportal_weather\Resolver\LocationWeatherResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Construct new ChainLocationWeatherResolver instance.
   *
   * @param \Drupal\myportal_weather\Resolver\LocationWeatherResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritDoc}
   */
  public function addResolver(LocationWeatherResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritDoc}
   */
  public function getResolvers(): array {
    return $this->resolvers;
  }

  /**
   * {@inheritDoc}
   */
  public function resolve(array $context): ?LocationInterface {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve($context);
      if ($result) {
        return $result;
      }
    }

    return NULL;
  }

}
