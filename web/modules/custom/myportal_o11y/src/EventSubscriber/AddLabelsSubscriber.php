<?php

declare(strict_types=1);

namespace Drupal\myportal_o11y\EventSubscriber;

use Drupal\o11y_metrics\Event\AddLabelEvent;
use Drupal\o11y_metrics\Event\MetricEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add specific k8s labels to all metrics.
 */
class AddLabelsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[MetricEvents::METRIC_ADD_LABEL][] = [
      'addLabels',
    ];

    return $events;
  }

  /**
   * Add k8s labels to the metric.
   *
   * @param \Drupal\o11y_metrics\Event\AddLabelEvent $event
   *   The Event to process.
   */
  public function addLabels(AddLabelEvent $event): void {
    $event->setLabelNames([
      'cluster_name',
      'container_name',
      'instance_id',
      'namespace_id',
      'pod_id',
      'zone',
    ]);
    $event->setLabelValues([
      getenv('K8S_CLUSTER'),
      getenv('K8S_CONTAINER'),
      getenv('K8S_NODE_NAME'),
      getenv('K8S_POD_NAMESPACE'),
      getenv('K8S_NAME'),
      getenv('K8S_ZONE'),
    ]);
  }

}
