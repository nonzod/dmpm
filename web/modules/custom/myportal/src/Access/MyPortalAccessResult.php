<?php

//@msg_clean

namespace Drupal\myportal\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Implement custom access results.
 *
 * @package Drupal\myportal\Access
 */
class MyPortalAccessResult extends AccessResult {

  const NO_RESTRICTED_ROLES = [
    'administrator',
    'sitemanager',
    'contentmanager',
  ];

  /**
   * Checks if logged user can edit a specific content section.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check content groups.
   * @param \Drupal\node\NodeInterface $node
   *   Content node.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account belongs to navigation editors isAllowed() will be TRUE,
   *   otherwise isForbidden() will be TRUE.
   */
  public static function allowedIfBelongToNavigationSection(AccountInterface $account, NodeInterface $node): AccessResult {
    
    if (self::isManager($account) || $account->hasPermission('bypass node access')) {
      return AccessResult::allowed();
    }

    if ($node->getOwner()->id() === $account->id()) {
      return AccessResult::allowed();
    }

    $group_manager = \Drupal::service('myaccess.group_manager');
    assert($group_manager instanceof GroupManagerInterface);

    if (empty(array_intersect($group_manager->getGroupIdsByUserAndRole($account, 'editor'), $group_manager->getGroupIdsByNode($node)))) {
      return AccessResult::forbidden();
    } elseif(!empty(array_intersect($group_manager->getGroupIdsByUserAndRole($account, 'local_admin'), $group_manager->getGroupIdsByNode($node)))) {
      // Access allowed for local admins
      return AccessResult::allowed();
    }

    $node_terms_id = [];
    if($node->hasField('field_navigation_section')){
      $terms_id = $node->get('field_navigation_section')->getValue();
      $node_terms_id = array_column($terms_id, 'target_id');
    }


    $user_manager = \Drupal::service('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);
    $user_terms_id = $user_manager->getTermsIdNavigationThatUserIsEditor($account);

    if (empty(array_intersect($node_terms_id, $user_terms_id))) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Checks if user has manager roles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return bool
   *   TRUE if user is a manager, FALSE otherwise.
   */
  public static function isManager(AccountInterface $account) {
    return !empty(array_intersect(self::NO_RESTRICTED_ROLES, $account->getRoles()));
  }

}
