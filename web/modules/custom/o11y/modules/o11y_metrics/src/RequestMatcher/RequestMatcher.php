<?php

namespace Drupal\o11y_metrics\RequestMatcher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Class RequestMatcher.
 */
class RequestMatcher implements RequestMatcherInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  /**
   * @param ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    PathMatcherInterface $pathMatcher
  ) {
    $this->configFactory = $configFactory;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * @inheritDoc
   */
  public function matches(Request $request) {
    $path = $request->getPathInfo();

    $patterns = $this->configFactory->get('o11y_metrics.settings')
      ->get('paths');

    return $this->pathMatcher->matchPath($path, $patterns);
  }

}
