<?php

namespace Drupal\myportal_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;

/**
 * Determines access to for group contents.
 */
class MyPortalGroupAccessCheck implements AccessInterface {

  const FIELD_CONTENT_VISIBILITY = 'field_content_visibility';
  const FIELD_APPLICATION_VISIBILITY = 'field_application_visibility';
  const CONTENT_VISIBILITY_COMMUNITY = 'community';
  const CONTENT_VISIBILITY_GROUP = 'group';
  const CONTENT_VISIBILITY_GROUP_ALL = 'group-all';
  const NODE_FORM_IDS = [
    'node_event_form',
    'node_event_edit_form',
    'node_page_form',
    'node_page_edit_form',
    'node_topic_form',
    'node_topic_edit_form',
  ];

  const NODE_CREATE_FORM_IDS = [
    'node_event_form',
    'node_page_form',
    'node_topic_form',
  ];


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

    // If the account can bypass all group access, return immediately.
    if ($account->hasPermission('bypass group access')) {
      return AccessResult::allowed();
    }

    if ($node instanceof Node) {
      $visibility = $node->get(self::FIELD_CONTENT_VISIBILITY)->getString();
      if ($visibility === self::CONTENT_VISIBILITY_GROUP_ALL) {
        return MyPortalGroupAccessResult::allowedIfHasSameGroups($account, $node);
      }

      return MyPortalGroupAccessResult::allowedIfHasGroupsInCommon($account, $node);
    }

    return AccessResult::neutral();
  }

  /**
   * Get the allowed visibility options for a given group type.
   *
   * @param mixed|null $group_type_id
   *   The group type. Can be NULL to get visibility when it is not in a group.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object that may have impact on the visibility options.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   Group.
   *
   * @return array
   *   An array of visibility options for the given group type.
   */
  public static function getAllowedVisibilityOptions($group_type_id,
                                                     AccountInterface $account = NULL,
                                                     GroupInterface $group = NULL): array {

    $visibility_options = [
      self::CONTENT_VISIBILITY_COMMUNITY => FALSE,
      self::CONTENT_VISIBILITY_GROUP_ALL => TRUE,
      self::CONTENT_VISIBILITY_GROUP => TRUE,
    ];

    \Drupal::moduleHandler()->alter('social_group_allowed_visibilities',
      $visibility_options, $group_type_id);

    return $visibility_options;
  }

}
