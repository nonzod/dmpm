<?php

namespace Drupal\myportal_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\myportal_news\Service\NewsFeedServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the NewsFeedBlock class.
 *
 * @Block(
 *   id = "myportal_news_feed",
 *   admin_label = @Translation("News Feed"),
 *   category = @Translation("MyPortal News Feed")
 * )
 *
 * @package Drupal\myportal_news\Plugin\Block
 */
class NewsFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The news feed provider.
   *
   * @var \Drupal\myportal_news\Service\NewsFeedServiceInterface
   */
  protected $newsFeedService;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Construct new NewsFeedBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\myportal_news\Service\NewsFeedServiceInterface $news_feed_service
   *   The feed service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NewsFeedServiceInterface $news_feed_service,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->newsFeedService = $news_feed_service;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $news_feed_service = $container->get('myportal_news.news_feed');
    assert($news_feed_service instanceof NewsFeedServiceInterface);

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountProxyInterface);

    $language_manager = $container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $news_feed_service,
      $current_user,
      $language_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if ($this->currentUser->isAnonymous()) {
      return [];
    }

    $lang_code = $this->languageManager->getCurrentLanguage()->getId();

    // Retrieve the news.
    $news = $this->newsFeedService->getNews($lang_code, 10, $this->currentUser);

    if (empty($news)) {
      return [];
    }

    return [
      '#theme' => 'myportal_news_marquee',
      '#news' => $news,
      '#account_id' => $this->currentUser->id(),
      '#lang_code' => $lang_code,
      '#attributes' => [
        'class' => ['news-feed-list'],
      ],
    ];
  }

}
