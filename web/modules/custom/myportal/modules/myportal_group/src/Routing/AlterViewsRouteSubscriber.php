<?php

namespace Drupal\myportal_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the AlterViewsRouteSubscriber class.
 *
 * @package Drupal\myportal_group\Routing
 */
class AlterViewsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('view.groups.page_user_groups')) {
      $route->setDefault('view_id', 'user_groups');
      $route->setDefault('display_id', 'page_1');
    }
  }

}
