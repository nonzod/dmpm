<?php

declare(strict_types=1);

namespace Drupal\myaccess\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('user.login')) {
      $route->setPath('/admin/login');
    }

    if ($route = $collection->get('user.logout')) {
      $route->setDefault('_controller', '\Drupal\myaccess\Controller\UserController::logout');
    }

    if ($route = $collection->get('flag.action_link_flag')) {
      $route->setDefault('_controller', '\Drupal\myaccess\Controller\CustomActionLinkController::flag');
    }

    // If the stress test is active we add a new route used in test.
    if (filter_var(getenv('STRESS_TEST_ENABLED'), FILTER_VALIDATE_BOOL) === TRUE) {
      $route = new Route("/oidc/login-with-access-token");
      $route->setDefaults([
        '_title' => 'Authenticate with Access Token',
        '_controller' => '\Drupal\myaccess\Controller\UserController::loginWithAccessToken',
      ])
        ->setRequirement('_user_is_logged_in', 'FALSE');
      $collection->add("myaccess.login_with_access_token", $route);
    }
  }

}
