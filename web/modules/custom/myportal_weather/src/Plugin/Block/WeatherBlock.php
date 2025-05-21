<?php

namespace Drupal\myportal_weather\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\myportal_weather\Resolver\ChainLocationWeatherResolverInterface;
use Drupal\myportal_weather\Service\Weather\WeatherProviderInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a weather block.
 *
 * @Block(
 *   id = "myportal_weather_block",
 *   admin_label = @Translation("WeatherWidget"),
 *   category = @Translation("MyPortal")
 * )
 */
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The location resolver chain.
   *
   * @var \Drupal\myportal_weather\Resolver\ChainLocationWeatherResolverInterface
   */
  protected $locationResolver;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The weather service.
   *
   * @var \Drupal\myportal_weather\Service\Weather\WeatherProviderInterface
   */
  protected $weatherProvider;

  /**
   * Construct new WeatherBlock instance.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\myportal_weather\Resolver\ChainLocationWeatherResolverInterface $location_resolver
   *   The location resolver.
   * @param \Drupal\myportal_weather\Service\Weather\WeatherProviderInterface $weather_provider
   *   The weather plugin manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ChainLocationWeatherResolverInterface $location_resolver,
    WeatherProviderInterface $weather_provider,
    AccountInterface $current_user,
    UserDataInterface $user_data,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->locationResolver = $location_resolver;
    $this->weatherProvider = $weather_provider;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    $location_resolver = $container->get('myportal_weather.chain_location_weather_resolver');
    assert($location_resolver instanceof ChainLocationWeatherResolverInterface);

    $weather_provider = $container->get('myportal_weather.weather.openweathermap');
    assert($weather_provider instanceof WeatherProviderInterface);

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountInterface);

    $user_data = $container->get('user.data');
    assert($user_data instanceof UserDataInterface);

    $language_manager = $container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $location_resolver,
      $weather_provider,
      $current_user,
      $user_data,
      $language_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Build context info array.
    $units = $this->userData->get('myportal_weather', $this->currentUser->id(), 'degrees');
    $context = [
      'user_id' => $this->currentUser->id(),
      'units' => in_array($units, ['imperial', 'metric']) ? $units : 'metric',
      'lang' => $this->languageManager->getCurrentLanguage()->getId(),
    ];

    // Retrieve the location for current user.
    $location = $this->locationResolver->resolve($context);
    if (empty($location)) {
      return ['#markup' => $this->t('Please configure a valid location for retrieve the weather information.')];
    }

    $build = [
      '#theme' => 'myportal_weather_widget',
      '#weather' => $this->weatherProvider->getCurrentWeatherInfo($location, $context),
      '#location' => $location,
      '#context' => $context,
      '#cache' => [
        'context' => ['user'],
        'tags' => ['user:' . $this->currentUser->id()],
        'max-age' => 60 * 60,
      ],
    ];

    // Add library.
    $build['#attached']['library'][] = 'myportal_weather/weather_widget';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

}
