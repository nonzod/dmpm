<?php

namespace Drupal\myportal\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class MyPortalRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class MyPortalRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Set admin route the path defined in layout_paragraphs.routing.
    // See https://wellnet.atlassian.net/browse/MEN-922
    if ($route = $collection->get('layout_paragraphs.builder.formatter')) {
      $route->setOption('_admin_route', TRUE);
    }
    if ($route = $collection->get('layout_paragraphs.builder.choose_component')) {
      $route->setOption('_admin_route', TRUE);
    }
    if ($route = $collection->get('layout_paragraphs.builder.edit_item')) {
      $route->setOption('_admin_route', TRUE);
    }
    if ($route = $collection->get('layout_paragraphs.builder.insert')) {
      $route->setOption('_admin_route', TRUE);
    }
    if ($route = $collection->get('layout_paragraphs.builder.duplicate_item')) {
      $route->setOption('_admin_route', TRUE);
    }
    if ($route = $collection->get('layout_paragraphs.builder.delete_item')) {
      $route->setOption('_admin_route', TRUE);
    }
    if ($route = $collection->get('layout_paragraphs.builder.reorder')) {
      $route->setOption('_admin_route', TRUE);
    }
  }

}
