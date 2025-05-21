<?php

namespace Drupal\myportal_weather\Resolver;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Drupal\myportal_weather\LocationInterface;
use Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface;
use Drupal\user\UserInterface;

/**
 * Defines the UserDefaultLocationWeatherResolver class.
 *
 * @package Drupal\myportal_weather\Resolver
 */
class UserDefaultLocationWeatherResolver implements LocationWeatherResolverInterface {

  use LoggerChannelTrait;

  /**
   * The current logged user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * THe geocoding provider service.
   *
   * @var \Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface
   */
  protected $geocodingProvider;

  /**
   * The myportal_weather.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Construct new UserDefaultLocationWeatherResolver instance.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The user manager service.
   * @param \Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface $geocoding_provider
   *   The geocoding provider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    UserManagerInterface $user_manager,
    GeocodingProviderInterface $geocoding_provider,
    ConfigFactoryInterface $config_factory
  ) {
    $this->currentUser = $current_user;
    $this->userManager = $user_manager;
    $this->geocodingProvider = $geocoding_provider;
    $this->config = $config_factory->get('myportal_weather.settings');
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function resolve(array $context): ?LocationInterface {
    $user = !empty($context['user']) && $context['user'] instanceof UserInterface ? $context['user'] : $this->currentUser;
    if ($user->isAnonymous()) {
      return NULL;
    }

    // Retrieve the location for group membership or HDP data stored.
    $group_location = $this->userManager->getGroupScopeForUser($user, GroupManagerInterface::SCOPE_LOCATION);

    if (!empty($group_location)) {

      // Use group name how location name.
      $location_name = $group_location->label();

      // Search and convert custom location name.
      $map_custom_location = $this->config->get('location_hdp_mapping');
      $map_custom_location = is_array($map_custom_location) ? $map_custom_location : [];
      $location_name = $map_custom_location[$location_name] ?? $location_name;
      if (empty($location_name)) {
        return NULL;
      }

      // Retrieve the coordinates.
      $locations = $this->geocodingProvider->getCoordinatesByLocationName($location_name);

      if (empty($locations)) {
        $this->getLogger('myportal_weather')
          ->warning("Location \"@location_name\" (from scope @scope) not found.", [
            '@location_name' => $location_name,
            '@scope' => GroupManagerInterface::SCOPE_LOCATION,
          ]);

        return NULL;
      }

      return reset($locations);
    }

    // Not found location, use a location from country map.
    $group_country = $this->userManager->getGroupScopeForUser($user, GroupManagerInterface::SCOPE_COUNTRY);
    $map_country2city = $this->config->get('country_location_mapping');
    $map_country2city = is_array($map_country2city) ? $map_country2city : [];

    if (!$group_country instanceof GroupInterface) {
      return NULL;
    }
    $group_country_label = $group_country->label();

    if (empty($map_country2city[$group_country_label])) {
      // Not found group country or not found a match in map.
      return NULL;
    }
    $location_name = $map_country2city[$group_country_label];

    // Retrieve the coordinates.
    $locations = $this->geocodingProvider->getCoordinatesByLocationName($location_name);

    if (empty($locations)) {
      $this->getLogger('myportal_weather')
        ->warning("Location \"@location_name\" (from scope @scope) not found.", [
          '@location_name' => $location_name,
          '@scope' => GroupManagerInterface::SCOPE_COUNTRY,
        ]);

      return NULL;
    }

    return reset($locations);
  }

}
