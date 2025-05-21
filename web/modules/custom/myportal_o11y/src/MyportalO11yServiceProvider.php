<?php

declare(strict_types=1);

namespace Drupal\myportal_o11y;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * This class alters services defined in other modules to measure metrics.
 */
class MyportalO11yServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritDoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('myaccess.oidc_client')) {
      $container->getDefinition('myaccess.oidc_client')
        ->setClass('Drupal\myportal_o11y\OpenId\TraceableClient');
    }
  }

}
