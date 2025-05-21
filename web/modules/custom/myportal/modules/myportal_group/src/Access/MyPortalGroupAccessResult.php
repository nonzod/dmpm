<?php

namespace Drupal\myportal_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Implement custom access results.
 */
class MyPortalGroupAccessResult extends AccessResult {

  /**
   * Allows access if logged user and node exactly belong to the same groups.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check content groups.
   * @param \Drupal\node\NodeInterface $node
   *   The content to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the same groups, isAllowed() will be TRUE, otherwise
   *   isForbidden() will be TRUE.
   */
  public static function allowedIfHasSameGroups(AccountInterface $account, NodeInterface $node): AccessResult {
    /** @var \Drupal\myaccess\GroupManagerInterface $groupManager */
    $groupManager = \Drupal::service('myaccess.group_manager');

    //return ($groupManager->hasSameGroups($node, $account)) ? AccessResult::allowed() : AccessResult::forbidden();
    return ($groupManager->hasSameGroupsInSameScope($node, $account)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Allows access if logged user and node have at least one group in common.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check content groups.
   * @param \Drupal\node\NodeInterface $node
   *   The content to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the user and node have at least one group in common, isAllowed() will
   *   be TRUE, otherwise isNeutral() will be TRUE.
   */
  public static function allowedIfHasGroupsInCommon(AccountInterface $account, NodeInterface $node): AccessResult {
    /** @var \Drupal\myaccess\GroupManagerInterface $groupManager */
    $groupManager = \Drupal::service('myaccess.group_manager');

    return ($groupManager->hasGroupsInCommon($node, $account)) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
