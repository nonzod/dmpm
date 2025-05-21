<?php

namespace Drupal\myportal_youtube;

use Drupal\Core\Config\ConfigFactory;
use Drupal\key\KeyRepository;
use Madcoda\Youtube\Youtube;

/**
 * Class YouTube Block Service.
 */
class YoutubeService {

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * The key repository object.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected KeyRepository $keyRepository;

  /**
   * The YouTube client.
   *
   * @var \Madcoda\Youtube\Youtube
   */
  private Youtube $client;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\key\KeyRepository $key_repository
   *   The key repository.
   */
  public function __construct(ConfigFactory $config_factory, KeyRepository $key_repository) {
    $this->configFactory = $config_factory;
    $this->keyRepository = $key_repository;
  }

  /**
   * Get Api Key.
   *
   * @return string|null
   *   The api key.
   */
  public function getApiKey(): ?string {
    $apiKeyName = $this->configFactory->get('myportal_youtube.settings')->get('api_key_name');
    $apiKey = NULL;
    if (!empty($apiKeyName)) {
      $apiKey = $this->keyRepository->getKey($apiKeyName)->getKeyValue();
    }

    return $apiKey;
  }

  /**
   * Get API client.
   *
   * @return \Madcoda\Youtube\Youtube
   *   The client.
   *
   * @throws \Exception
   */
  public function getClient(): Youtube {
    $this->client = new Youtube(['key' => $this->getApiKey()]);

    return $this->client;
  }

  /**
   * Get playlist by id.
   *
   * @param string $id
   *   The playlist id.
   *
   * @return false|\StdClass
   *   The playlist or false if not found.
   *
   * @throws \Exception
   */
  public function getPlaylistById(string $id) {
    return $this->getClient()->getPlaylistById($id);
  }

  /**
   * Get playlist items by playlist id.
   *
   * @param string $id
   *   The playlist id.
   * @param int $maxResults
   *   The max results.
   *
   * @return array|false
   *   The playlist items or false if not found.
   *
   * @throws \Exception
   */
  public function getPlaylistItemsByPlaylistId(string $id, int $maxResults = 50) {
    return $this->getClient()->getPlaylistItemsByPlaylistId($id, $maxResults);
  }

}
