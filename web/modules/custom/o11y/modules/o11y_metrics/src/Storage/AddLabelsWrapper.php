<?php

namespace Drupal\o11y_metrics\Storage;

use Drupal\o11y_metrics\Event\AddLabelEvent;
use Drupal\o11y_metrics\Event\MetricEvents;
use Prometheus\Storage\Adapter;

/**
 * Class AddLabelsWrapper.
 */
class AddLabelsWrapper implements Adapter {

  /**
   * @var \Prometheus\Storage\Adapter
   */
  private $original;

  /**
   * AddLabelsWrapper constructor.
   *
   * @param $original
   */
  public function __construct($original) {
    assert($original instanceof Adapter);

    $this->original = $original;
  }

  /**
   * @inheritDoc
   */
  public function collect(): array {
    return $this->original->collect();
  }

  /**
   * @inheritDoc
   */
  public function wipeStorage(): void {
    $this->original->wipeStorage();
  }

  /**
   * @inheritDoc
   */
  public function updateHistogram(array $data): void {
    $this->original->updateHistogram($this->addLabels($data));
  }

  /**
   * @inheritDoc
   */
  public function updateGauge(array $data): void {
    $this->original->updateGauge($this->addLabels($data));
  }

  /**
   * @inheritDoc
   */
  public function updateCounter(array $data): void {
    $this->original->updateCounter($this->addLabels($data));
  }

  /**
   * @param $data
   *
   * @return array
   */
  protected function addLabels($data): array {
    $label_names = $data['labelNames'];
    $label_values = $data['labelValues'];

    $event = new AddLabelEvent($label_names, $label_values);

    \Drupal::service('event_dispatcher')
      ->dispatch(
        MetricEvents::METRIC_ADD_LABEL,
        $event
      );

    $data['labelNames'] = array_merge($label_names, $event->getLabelNames());
    $data['labelValues'] = array_merge($label_values, $event->getLabelValues());

    return $data;
  }

  /**
   * @inheritDoc
   */
  public function updateSummary(array $data): void
  {
    // TODO: Implement updateSummary() method.
  }

}
