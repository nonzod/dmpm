<?php

namespace Drupal\o11y_metrics;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\o11y_metrics\Storage\AddLabelsWrapper;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Metrics.
 */
class Metrics implements MetricsInterface {

  /**
   * @var \Prometheus\CollectorRegistry
   */
  private $registry;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $event_dispatcher;

  /**
   * Metrics constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EventDispatcherInterface $event_dispatcher
  ) {
    $settings = $config_factory->get('o11y_metrics.settings');
    $host = $settings->get('host');
    $port = $settings->get('port');
    $password = $settings->get('password');
    $this->registry = new CollectorRegistry(
      new AddLabelsWrapper(
        new Redis(
          [
            'host' => $host,
            'port' => $port,
            'password' => $password ?? '',
          ]
        )));

    $this->configFactory = $config_factory;
    $this->event_dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritDoc}
   */
  public function getOrRegisterCounter(
    $namespace,
    $name,
    $help,
    $labels = []
  ): Counter {
    return $this->registry->getOrRegisterCounter($namespace, $name, $help,
      $labels);
  }

  /**
   * {@inheritDoc}
   */
  public function getOrRegisterGauge(
    $namespace,
    $name,
    $help,
    $labels = []
  ): Gauge {
    return $this->registry->getOrRegisterGauge($namespace, $name, $help,
      $labels);
  }

  /**
   * {@inheritDoc}
   */
  public function getOrRegisterHistogram(
    $namespace,
    $name,
    $help,
    $labels = [],
    array $buckets = NULL
  ): Histogram {
    return $this->registry->getOrRegisterHistogram($namespace, $name, $help,
      $labels, $buckets);
  }

  /**
   * {@inheritDoc}
   */
  public function render(): Response {
    $renderer = new RenderTextFormat();
    $result = $renderer->render($this->registry->getMetricFamilySamples());

    $response = new Response($result);
    $response->headers->set('Content-type', RenderTextFormat::MIME_TYPE);

    return $response;
  }

}
