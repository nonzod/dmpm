<?php

namespace Drupal\myportal_news\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Drupal\myportal_news\Exception\FetchFeedException;
use Drupal\myportal_news\Exception\ParseFeedException;
use Drupal\myportal_news\Service\ProviderFeed\ProviderFeedInterface;

/**
 * Defines the NewsFeedService class.
 *
 * @package Drupal\myportal_news\Service
 */
class NewsFeedService implements NewsFeedServiceInterface {

  use LoggerChannelTrait;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The provider service for FeedNews.
   *
   * @var \Drupal\myportal_news\Service\ProviderFeed\ProviderFeedInterface
   */
  protected $newsProvider;

  /**
   * User manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * Construct new NewsFeedService instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\myportal_news\Service\ProviderFeed\ProviderFeedInterface $provider
   *   The provider service.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The user manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    ProviderFeedInterface $provider,
    UserManagerInterface $user_manager,
    AccountProxyInterface $current_user
  ) {
    $this->cache = $cache;
    $this->newsProvider = $provider;
    $this->config = $config_factory->get('myportal_news.settings');
    $this->userManager = $user_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritDoc}
   */
  public function getNews(string $lang_code, int $count = 10, AccountInterface $account = NULL): array {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    $group_country = $this->userManager->getGroupScopeForUser($account, GroupManagerInterface::SCOPE_COUNTRY);
    if (!$group_country instanceof GroupInterface) {
      return [];
    }

    $feed_url = $this->getFeedUrl((string) $group_country->id(), $lang_code);
    if (empty($feed_url)) {
      return [];
    }

    // Load cache time from config.
    $expire_time_cache = $this->config->get('cache_maximum_age');
    $data = [];

    // Build the cache id.
    $cid = "myportal_news:get_news:{$group_country->id()}:{$lang_code}";
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {

      try {
        // Retrieve the NewsFeed.
        $data = $this->getNewsFromProvider($feed_url, [
          'group_id' => $group_country->id(),
          'account_id' => $account->id(),
          'language_code' => $lang_code,
          'limit' => $count,
        ]);

        // Store data in cache.
        $this->cache->set($cid, $data, time() + $expire_time_cache);
      }
      catch (FetchFeedException $exception) {
        $this->getLogger('myportal_news')
          ->warning($exception->getMessage());
        $data = [];
      }
      catch (ParseFeedException | \Throwable $exception) {
        $this->getLogger('myportal_news')
          ->error($exception->getMessage());

        // Save an empty array for not retry again before 5 minutes.
        $this->cache->set($cid, $data, time() + 300);
      }
    }

    return $data;
  }

  /**
   * Get news from provider.
   *
   * @param string $url
   *   The source url.
   * @param array $parameters
   *   The context parameters.
   *
   * @return array
   *   An array of News.
   *
   * @throws \Drupal\myportal_news\Exception\FetchFeedException
   * @throws \Drupal\myportal_news\Exception\ParseFeedException
   */
  public function getNewsFromProvider(string $url, array $parameters): array {

    try {
      $source = $this->newsProvider->fetch($url, $parameters);
    }
    catch (\Throwable $exception) {
      throw new FetchFeedException($exception->getMessage(), (int) $exception->getCode(), $exception);
    }

    try {
      return $this->newsProvider->parse($source, $parameters);
    }
    catch (\Throwable $exception) {
      throw new ParseFeedException($exception->getMessage(), (int) $exception->getCode(), $exception);
    }
  }

  /**
   * Retrieve the feed Url from configurations.
   *
   * @param string $group_id
   *   The group id.
   * @param string $lang_code
   *   The language code.
   *
   * @return string|null
   *   The feed URL.
   */
  protected function getFeedUrl(string $group_id, string $lang_code) {
    $feeds = $this->config->get('feeds');
    if (!empty($feeds[$group_id][$lang_code])) {
      return $feeds[$group_id][$lang_code];
    }
    elseif (!empty($feeds[$group_id]['en'])) {
      return $feeds[$group_id]['en'];
    }
    else {
      return NULL;
    }
  }

}
