<?php

namespace Drupal\o11y_metrics\StackMiddleware;

use Drupal\o11y_metrics\MetricsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class MiddlewareBase.
 */
class MiddlewareBase {

  /**
   * @var \Drupal\o11y_metrics\MetricsInterface
   */
  protected $metrics;

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
   */
  protected $matcher;

  /**
   * MemoryMiddleware constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   * @param \Drupal\o11y_metrics\MetricsInterface $metrics
   * @param \Symfony\Component\HttpFoundation\RequestMatcherInterface $matcher
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    MetricsInterface $metrics,
    RequestMatcherInterface $matcher
  ) {
    $this->metrics = $metrics;
    $this->httpKernel = $http_kernel;
    $this->matcher = $matcher;
  }

  /**
   * Return TRUE if the middleware apply to this Request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return bool
   *   TRUE if the middleware apply to this Request.
   */
  protected function apply(Request $request): bool {
    $path = $request->getPathInfo();

    // Always exclude the /metrics path.
    if ($path != '/metrics') {

      // Exclude requests without a route name.
      $route_name = $request->get('_route');
      if ($route_name != '') {

        // Include only configured paths.
        if ($this->matcher->matches($request)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   */
  protected function getRouteName(Request $request): string {
    return $request->get('_route');
  }

}
