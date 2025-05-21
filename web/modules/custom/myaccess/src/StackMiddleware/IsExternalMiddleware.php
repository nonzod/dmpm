<?php

declare(strict_types=1);

namespace Drupal\myaccess\StackMiddleware;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a middleware to check if the request is external.
 *
 * The 'external' value is added to the attributes of the Request object.
 */
class IsExternalMiddleware implements HttpKernelInterface {

  public const HEADER = 'X-Menarini-01';

  private const HEADER_VALUE = 'external-access';

  public const KEY = 'external';

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected HttpKernelInterface $httpKernel;

  /**
   * Constructs a IsExternalMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $request->attributes->set(self::KEY, $this->isExternal($request));

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Check if the request comes from the outside of the Menarini network.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   TRUE if the request is external.
   */
  private function isExternal(Request $request): bool {
    if ($this->hasExternalHeader($request->headers)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Return TRUE if the request contains HEADER, and it's value is HEADER_VALUE.
   *
   * @param \Symfony\Component\HttpFoundation\HeaderBag $headers
   *   Headers (taken from the $_SERVER).
   *
   * @return bool
   *   TRUE if the request contains HEADER, and it's value is HEADER_VALUE.
   */
  private function hasExternalHeader(HeaderBag $headers): bool {
    return $headers->has(self::HEADER) &&
      $headers->get(self::HEADER) === self::HEADER_VALUE;
  }

}
