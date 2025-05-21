<?php

declare(strict_types=1);

namespace Drupal\myaccess\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\myaccess\Entity\Application;
use Symfony\Component\Routing\Route;

/**
 * Class for converting a path parameter to the object it represents.
 */
class ApplicationConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $application = Application::load($value);

    if (NULL === $application) {
      return NULL;
    }

    return $application;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] === 'myaccess:application') {
      return TRUE;
    }

    return FALSE;
  }

}
