<?php

namespace Drupal\o11y_traces;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class O11yServiceProvider.
 */
class O11YTracesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('event_dispatcher')
      ->setClass('Drupal\o11y_traces\EventDispatcher\TraceableEventDispatcher')
      ->addMethodCall('setOpentracing',
        [new Reference('o11y_traces.opentracing')]);
    $container->getDefinition('http_client_factory')
      ->setClass('Drupal\o11y_traces\Http\ClientFactory');
  }

}
