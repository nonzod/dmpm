<?php

declare(strict_types=1);

namespace Drupal\social_xoverride\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Restrict access to Social Group routes.
    $canonical = $collection->get('entity.group.canonical');
    if ($canonical != NULL) {
      $canonical->setRequirement('_permission', 'access group overview');
    }

    $stream = $collection->get('social_group.stream');
    if ($stream != NULL) {
      $stream->setRequirement('_permission', 'access group overview');
    }

    $my_groups = $collection->get('social_group.my_groups');
    if ($my_groups != NULL) {
      $my_groups->setRequirement('_permission', 'access group overview');
    }
  }

}
