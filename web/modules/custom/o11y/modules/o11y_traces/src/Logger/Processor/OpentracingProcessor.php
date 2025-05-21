<?php

namespace Drupal\o11y_traces\Logger\Processor;

use Drupal\o11y_traces\Opentracing;

/**
 * Class OpentracingProcessor
 */
class OpentracingProcessor {

  /**
   * @var \Drupal\o11y_traces\Opentracing
   */
  private $tracing;

  /**
   * OpentracingProcessor constructor.
   *
   * @param \Drupal\o11y_traces\Opentracing $tracing
   */
  public function __construct(Opentracing $tracing) {
    $this->tracing = $tracing;
  }

  /**
   * @param array $record
   *
   * @return array
   */
  public function __invoke(array $record) {
    $record['extra']['trace_id'] = $this->tracing->getTraceId();

    return $record;
  }

}
