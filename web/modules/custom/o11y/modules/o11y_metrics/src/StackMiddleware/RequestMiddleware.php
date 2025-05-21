<?php

namespace Drupal\o11y_metrics\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class RequestMiddleware.
 */
class RequestMiddleware extends MiddlewareBase implements HttpKernelInterface {

  /**
   * {@inheritDoc}
   */
  public function handle(
    Request $request,
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) {

    if ($this->apply($request)) {
      $requests = $this->metrics->getOrRegisterCounter(
        'php',
        'request',
        'The number of requests',
        ['path']
      );

      $requests->inc([$this->getRouteName($request)]);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
