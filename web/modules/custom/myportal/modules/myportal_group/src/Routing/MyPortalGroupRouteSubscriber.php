<?php

namespace Drupal\myportal_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class MyPortalGroupRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class MyPortalGroupRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('entity.node.canonical')) {
      $requirements = $route->getRequirements();
      $requirements['_myportal_content_access'] = 'Drupal\myportal_group\Access\MyPortalGroupAccessCheck::access';
      $route->setRequirements($requirements);
    }
  }

}
