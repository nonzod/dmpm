<?php

namespace Drupal\myportal_weather\Resolver;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\myportal_weather\OWM\Location;
use Drupal\myportal_weather\LocationInterface;

/**
 * Provides the default location of site, taking it directly from config site.
 *
 * @package Drupal\myportal_weather\Resolver
 */
class DefaultLocationWeatherResolver implements LocationWeatherResolverInterface {

  /**
   * The myportal_weather.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Construct new DefaultLocationWeatherResolver instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('myportal_weather.settings');
  }

  /**
   * {@inheritDoc}
   */
  public function resolve(array $context): ?LocationInterface {
    try {
      $default_location = $this->config->get('default.location');

      return Location::fromArray($default_location);
    }
    catch (\Throwable $exception) {
      return NULL;
    }
  }

}
