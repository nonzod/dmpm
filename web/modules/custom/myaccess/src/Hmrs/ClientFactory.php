<?php

declare(strict_types=1);

namespace Drupal\myaccess\Hmrs;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Factory service to retrieve the correct instance of HMRS client to use.
 */
class ClientFactory {

  /**
   * Return the correct instance of HMRS client to use.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The Config factory service.
   *
   * @return \Drupal\myaccess\Hmrs\ClientInterface
   *   The correct instance of HMRS client to use.
   */
  final public static function getClient(ConfigFactoryInterface $config): ClientInterface {
    $client = $config->get('myaccess.settings')->get('hmrs.client');

    switch ($client) {
      case 'api':
        /** @var \Drupal\myaccess\Hmrs\ClientInterface $apiClient */
        $apiClient = \Drupal::service('myaccess.hmrs_api_client');

        return $apiClient;

      case 'csv':
      default:
        /** @var \Drupal\myaccess\Hmrs\ClientInterface $csvClient */
        $csvClient = \Drupal::service('myaccess.hmrs_csv_client');

        return $csvClient;
    }

  }

}
