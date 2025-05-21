<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\myaccess\EventSubscriber\StressTests\UpdateUserDataSubscriber;

/**
 * Defines a service provider for the Myaccess module.
 */
class MyaccessServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the `session_configuration` service to made it configurable.
    $session_configuration = $container->getDefinition('session_configuration');
    $session_configuration
      ->setClass('Drupal\myaccess\Request\SessionConfiguration');

    // If the stress test is active we replace the following listener.
    if (filter_var(getenv('STRESS_TEST_ENABLED'), FILTER_VALIDATE_BOOL) === TRUE
      && $event_subscriber = $container->getDefinition('myaccess.update_user_data_subscriber')) {
      $event_subscriber->setClass(UpdateUserDataSubscriber::class);
    }
  }

}
