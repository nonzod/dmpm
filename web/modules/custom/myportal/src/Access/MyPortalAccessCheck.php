<?php

namespace Drupal\myportal\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Determines access to for contents.
 */
class MyPortalAccessCheck implements AccessInterface {

  /**
   * CurrentRouteMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * MyPortalGroupAccessCheck constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   CurrentRouteMatch service.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Checks access to the group content based on visibility option selected.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $node = $this->currentRouteMatch->getParameter('node');

    if ($node instanceof NodeInterface) {
      return MyPortalAccessResult::allowedIfBelongToNavigationSection($account, $node);
    }

    return AccessResult::neutral();
  }

}
