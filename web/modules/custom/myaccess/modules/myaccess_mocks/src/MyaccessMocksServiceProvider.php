<?php

namespace Drupal\myaccess_mocks;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider to replace the myaccess.oidc_client class with a mock.
 *
 * @package Drupal\myaccess_mocks
 */
class MyaccessMocksServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('myaccess.oidc_client')
      ->setClass('Drupal\myaccess_mocks\OpenId\MockedClient')
      ->setArguments([
        new Reference('entity_type.manager'),
        new Reference('user.auth'),
        new Reference('keyvalue.expirable'),
        new Reference('file_system'),
        new Reference('extension.list.module')
      ]);
  }

}
