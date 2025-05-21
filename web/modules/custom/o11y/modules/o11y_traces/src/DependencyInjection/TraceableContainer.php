<?php

namespace Drupal\o11y_traces\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;

/**
 * Extends the Drupal container class to trace service instantiations.
 */
class TraceableContainer extends Container {

  /**
   * @var \Drupal\o11y_traces\Opentracing
   */
  private $opentracing;

  /**
   * @var bool
   */
  private $hasOpentracing = FALSE;

  /**
   * @param string $id
   * @param int $invalidBehavior
   *
   * @return object
   */
  public function get(
    $id,
    $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE
  ) {
    if (!$this->opentracing && $this->has('o11y_traces.opentracing')) {
      $this->opentracing = parent::get('o11y_traces.opentracing');
      $this->hasOpentracing = TRUE;
    }

    if ('o11y_traces.opentracing' === $id) {
      return $this->opentracing;
    }

    if ($this->hasOpentracing) {
      $e = $this->opentracing->startSpan($id);
    }

    $service = parent::get($id, $invalidBehavior);

    if ($this->hasOpentracing) {
      $e->finish();
    }

    return $service;
  }

}
