<?php

namespace Drupal\o11y_metrics\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class MemoryMiddleware.
 */
class MemoryMiddleware extends MiddlewareBase implements HttpKernelInterface {

  /**
   * {@inheritDoc}
   */
  public function handle(
    Request $request,
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) {
    $handle = $this->httpKernel->handle($request, $type, $catch);

    if ($this->apply($request)) {
      $histogram = $this->metrics->getOrRegisterHistogram(
        'php',
        'memory_peak',
        'The peak of memory allocated by PHP',
        ['path'],
        [20000000, 40000000, 60000000]
      );

      $histogram->observe(
        memory_get_usage(FALSE),
        [$this->getRouteName($request)]
      );

    }

    return $handle;
  }

}
