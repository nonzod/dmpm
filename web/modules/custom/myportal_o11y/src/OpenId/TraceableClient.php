<?php

declare(strict_types=1);

namespace Drupal\myportal_o11y\OpenId;

use Drupal\myaccess\OpenId\Client;

/**
 * Extends the openid client class to trace responses time.
 */
class TraceableClient extends Client {

  /**
   * {@inheritDoc}
   */
  public function getExternalApplications(string $username, bool $external): array {
    $start = time();

    $applications = parent::getExternalApplications($username, $external);

    $end = time() - $start;

    /** @var \Drupal\o11y_metrics\MetricsInterface $metrics */
    $metrics = \Drupal::service('o11y_metrics.metrics');
    $histogram = $metrics->getOrRegisterHistogram('myaccess', 'remote_time',
      'The Time of a remote request', ['type']);
    $histogram->observe($end, ['applications']);

    return $applications;
  }

}
