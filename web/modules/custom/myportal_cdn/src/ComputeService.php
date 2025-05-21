<?php

declare(strict_types=1);

namespace Drupal\myportal_cdn;

use Drupal\Core\Config\ConfigFactory;

/**
 * Interact with the GCP compute api to invalidate the cdn cache.
 */
class ComputeService {

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * CdnCacheTagsInvalidator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The Config factory service.
   */
  public function __construct(ConfigFactory $config) {
    $this->config = $config->get('myportal_cdn.settings');
  }

  /**
   * Invalidate a path from the cdn.
   *
   * @param string $path
   *   The path to invalidate.
   *
   * @throws \Google\Exception
   */
  public function invalidate(string $path): void {
    $client = $this->getComputeService();

    $invalidation_rule = new \Google_Service_Compute_CacheInvalidationRule();
    $invalidation_rule->setPath($path);

    $project = $this->config->get('project');
    $url_map = $this->config->get('url_map');

    $client->urlMaps->invalidateCache(
      $project,
      $url_map,
      $invalidation_rule
    );
  }

  /**
   * Return a client to interact with the GCP compute api.
   *
   * @return \Google_Service_Compute
   *   A client to interact with the GCP compute api.
   *
   * @throws \Google\Exception
   */
  private function getComputeService(): \Google_Service_Compute {
    $client = new \Google_Client();
    $client->setAuthConfig($this->config->get('auth_config_path'));
    $client->setScopes([
      'https://www.googleapis.com/auth/compute',
    ]);

    return new \Google_Service_Compute($client);
  }

}
