<?php

namespace Drupal\myportal_weather\Resolver;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\myportal_weather\OWM\Location;
use Drupal\myportal_weather\LocationInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Provides the location from user preference.
 *
 * @package Drupal\myportal_weather\Resolver
 */
class UserPreferenceLocationWeatherResolver implements LocationWeatherResolverInterface {

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The current logged user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Construct new UserPreferenceLocationWeatherResolver instance.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(UserDataInterface $user_data, AccountProxyInterface $current_user) {
    $this->userData = $user_data;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritDoc}
   */
  public function resolve(array $context): ?LocationInterface {
    $user = isset($context['user']) && $context['user'] instanceof UserInterface ? $context['user'] : $this->currentUser;

    if ($user->isAnonymous()) {
      return NULL;
    }

    $weather_location = $this->userData->get('myportal_weather', (int) $user->id(), 'location');
    if (empty($weather_location)) {
      return NULL;
    }

    try {
      return Location::fromArray($weather_location);
    }
    catch (\Throwable $exception) {
      return NULL;
    }
  }

}
