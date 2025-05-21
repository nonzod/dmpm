<?php

namespace Drupal\o11y_metrics\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\o11y_metrics\MetricsInterface;
use Prometheus\Storage\Redis;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MetricsController.
 */
class MetricsController extends ControllerBase {

  /**
   * @var \Drupal\o11y_metrics\MetricsInterface
   */
  private $metrics;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * MetricsController constructor.
   *
   * @param \Drupal\o11y_metrics\MetricsInterface $metrics
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   */
  public function __construct(
    MetricsInterface $metrics,
    ConfigFactoryInterface $config
  ) {
    $this->metrics = $metrics;
    $this->config = $config->get('o11y_metrics.settings');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('o11y_metrics.metrics'),
      $container->get('config.factory')
    );
  }

  /**
   * Render metrics.
   *
   * This method will also clear all the metrics collected if
   * purge_metrics_after_collect is TRUE.
   */
  public function metrics(Request $request) {
    \Drupal::service('page_cache_kill_switch')->trigger();


    $host = $this->config->get('host');
    $port = $this->config->get('port');
    $password = $this->config->get('password');

    $redis = new Redis(
      [
        'host' => $host,
        'port' => $port,
        'password' => $password ?? '',
      ]
    );

    $output = $this->metrics->render();

    if ($this->config->get('purge_metrics_after_collect')) {
      $redis->wipeStorage();
    }

    return $output;
  }

}
