<?php

namespace Drupal\o11y_metrics;

use Symfony\Component\HttpFoundation\Response;

/**
 * Interface MetricsInterface.
 */
interface MetricsInterface {

  /**
   * @param string $namespace e.g. cms
   * @param string $name e.g. requests
   * @param string $help e.g. The number of requests made.
   * @param array $labels e.g. ['controller', 'action']
   *
   * @return \Prometheus\Counter
   */
  public function getOrRegisterCounter($namespace, $name, $help, $labels = []);

  /**
   * @param $namespace
   * @param $name
   * @param $help
   * @param array $labels
   *
   * @return \Prometheus\Gauge
   */
  public function getOrRegisterGauge($namespace, $name, $help, $labels = []);

  /**
   * @param $namespace
   * @param $name
   * @param $help
   * @param array $labels
   * @param array|null $buckets
   *
   * @return \Prometheus\Histogram
   */
  public function getOrRegisterHistogram(
    $namespace,
    $name,
    $help,
    $labels = [],
    array $buckets = NULL
  );

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function render(): Response;

}
