<?php

declare(strict_types=1);

namespace Drupal\myportal_group;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for module myportal_group.
 */
class MyportalGroupServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace the overrides provided by social_group module.
    // The original override forces the group widget for topics and events to be
    // social_group_selector_widget. We need to use
    // myportal_group_selector_widget instead.
    $container->getDefinition('social_group.overrider')
      ->setClass('Drupal\myportal_group\MyPortalGroupSelectorWidgetConfigOverride')
      ->addArgument(new Reference('config.factory'));
  }

}
