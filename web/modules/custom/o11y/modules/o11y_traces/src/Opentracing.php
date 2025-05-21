<?php

namespace Drupal\o11y_traces;

use Jaeger\Config;
use Jaeger\SpanContext;
use OpenTracing\Span;

/**
 * Class Opentracing.
 */
class Opentracing {

  protected $tracer;

  protected $config;

  public function __construct() {
    $this->config = Config::getInstance();
    $this->config->gen128bit();

    $this->tracer = $this->config->initTracer('drupal', 'jaeger:6831');
  }

  public function startSpan(string $operation, array $tags = []): Span {
    $scope = $this->tracer->startActiveSpan($operation, ['tags' => $tags]);

    return $scope->getSpan();
  }

  public function flush() {
    $this->config->flush();
  }

  public function getTraceId() {
    $span = $this->tracer->getActiveSpan();
    if ($span && ($context = $span->getContext()) instanceof SpanContext) {
      return $context->traceIdLowToString();
    }

    return '';
  }

  public function getTracer() {
    return $this->tracer;
  }

}
