<?php

declare(strict_types=1);

namespace Drupal\myaccess\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Applications.
 *
 * @Block(
 *   id = "myaccess_applications",
 *   admin_label = @Translation("MyAccess applications")
 * )
 */
class ApplicationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The wrapped cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * ApplicationsBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  final public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              LanguageManagerInterface $language_manager,
                              StateInterface $state,
                              CacheBackendInterface $cache_backend) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->state = $state;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    $language_manager = $container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);

    $state = $container->get('state');
    assert($state instanceof StateInterface);

    $cache_backend = $container->get('cache.entity');
    assert($cache_backend instanceof CacheBackendInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $language_manager,
      $state,
      $cache_backend
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {

    $language = $this->languageManager->getCurrentLanguage()->getId();
    $welcome_message = !empty($this->state->get("myp_welcome_message_{$language}")) ? $this->state->get("myp_welcome_message_{$language}") : $this->t("Welcome");

    return [
      '#theme' => 'applications_wrapper',
      '#myp_message' => $welcome_message,
      '#cache' => [
        'tags' => ['myp:welcome:message'],
      ],
    ];
  }

}
