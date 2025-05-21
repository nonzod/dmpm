<?php

namespace Drupal\o11y_traces\StackMiddleware;

use Drupal\o11y_traces\Opentracing;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class OpentracingMiddleware.
 */
class OpentracingMiddleware implements HttpKernelInterface {

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  private $httpKernel;

  /**
   * @var \Drupal\o11y_traces\Opentracing
   */
  private $tracing;

  /**
   * MemoryMiddleware constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   * @param \Drupal\o11y_traces\Opentracing $tracing
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    Opentracing $tracing
  ) {
    $this->httpKernel = $http_kernel;
    $this->tracing = $tracing;
  }

  /**
   * {@inheritDoc}
   */
  public function handle(
    Request $request,
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) {
    $span = $this->tracing->startSpan('Request', [
      'http.method' => $request->getMethod(),
      'http.url' => $request->getRequestUri(),
    ]);

    $handle = $this->httpKernel->handle($request, $type, $catch);

    //    $span->finish();
    //    $this->tracing->flush();

    return $handle;
  }

}
