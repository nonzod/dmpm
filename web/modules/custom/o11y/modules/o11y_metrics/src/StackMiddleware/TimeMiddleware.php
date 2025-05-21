<?php

namespace Drupal\o11y_metrics\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class TimeMiddleware.
 */
class TimeMiddleware extends MiddlewareBase implements HttpKernelInterface {

  /**
   * {@inheritDoc}
   */
  public function handle(
    Request $request,
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) {
    $start = time();
    $handle = $this->httpKernel->handle($request, $type, $catch);

    if ($this->apply($request)) {
      $histogram = $this->metrics->getOrRegisterHistogram(
        'php',
        'time',
        'The Time of a request',
        ['path']
      );

      $histogram->observe(
        time() - $start,
        [$this->getRouteName($request)]
      );
    }

    return $handle;
  }

}
