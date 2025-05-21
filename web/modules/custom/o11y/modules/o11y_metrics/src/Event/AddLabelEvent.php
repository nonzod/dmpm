<?php

namespace Drupal\o11y_metrics\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AddLabelEvent.
 */
class AddLabelEvent extends Event {

  /**
   * @var array
   */
  private $label_names;

  /**
   * @var array
   */
  private $label_values;

  /**
   * AddLabelEvent constructor.
   *
   * @param array $label_names
   * @param array $label_values
   */
  public function __construct(array $label_names, array $label_values) {
    $this->label_names = $label_names;
    $this->label_values = $label_values;
  }

  /**
   * @return array
   */
  public function getLabelNames(): array {
    return $this->label_names;
  }

  /**
   * @param array $label_names
   */
  public function setLabelNames(array $label_names): void {
    $this->label_names = $label_names;
  }

  /**
   * @return array
   */
  public function getLabelValues(): array {
    return $this->label_values;
  }

  /**
   * @param array $label_values
   */
  public function setLabelValues(array $label_values): void {
    $this->label_values = $label_values;
  }

}
