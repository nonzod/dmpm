<?php

//@msg_clean

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Interface for Group managers.
 */
interface GroupManagerInterface {

  const GLOBAL = 'Global';

  const INTERNAL = 'Internal';

  const EXTERNAL = 'External';

  const MANAGER = 'Manager';

  const NO_MANAGER = 'Non Manager';

  const SCOPE_COMPANY = 'company';

  const SCOPE_DIVISION = 'division';

  const SCOPE_DEPARTMENT = 'department';

  const SCOPE_SUB_AREA = 'sub_area';

  const SCOPE_SUB_AREA_2 = 'sub_area_2';

  const SCOPE_SUB_AREA_3 = 'sub_area_3';

  const SCOPE_SUB_AREA_4 = 'sub_area_4';

  const SCOPE_SUB_AREA_5 = 'sub_area_5';

  const SCOPE_SUB_AREA_6 = 'sub_area_6';

  const SCOPE_SUB_AREA_7 = 'sub_area_7';

  const SCOPE_FUNCTION = 'function';

  const SCOPE_SUB_FUNCTION = 'sub_function';

  const SCOPE_LEGAL_ENTITY = 'legal_entity';

  const SCOPE_REGION = 'region';

  const SCOPE_COUNTRY = 'country';

  const SCOPE_SUB_REGION = 'sub_region';

  const SCOPE_LOCATION = 'location';

  const SCOPE_FUNCTIONAL_AREA = 'functional_area';

  const SCOPE_POSITION_AREA = 'position_area';

  const SCOPE_POSITION_TITLE = 'position_title';

  const CONTEXT_MYLINKS = 'mylinks';

  const CONTEXT_CONTENT = 'content';

  /**
   * Create a group if it doesn't exist yet.
   *
   * @param string $name
   *   The name of the group to create.
   * @param string $scope
   *   The group scope (functional or geographic).
   * @param string[] $context
   *   The group context (content or mylinks).
   *
   * @return \Drupal\group\Entity\Group
   *   A new or existing group.
   *
   * @throws \Drupal\myaccess\Exception\GroupNotCreatedException
   */
  public function createIfNotExists(string $name, string $scope, array $context): Group;

  /**
   * Add a user to a group.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $group_name
   *   The group name.
   */
  public function addUserToGroup(UserInterface $user, string $group_name): void;

  /**
   * Remove a user from a group.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $group_name
   *   The group name.
   */
  public function removeUserFromGroup(UserInterface $user, string $group_name): void;

  /**
   * Get group ids a node belongs to.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Content node object.
   *
   * @return array
   *   The groups ids.
   */
  public function getGroupIdsByNode(NodeInterface $node): array;

  /**
   * Get group ids a user belongs to.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The logged-in user.
   *
   * @return array
   *   The groups ids.
   */
  public function getGroupIdsByUser(AccountInterface $account): array;

  /**
   * Get group ids a user belongs to with a specific role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The logged-in user.
   * @param string $role_id
   *   The role id (es. editor).
   *
   * @return array
   *   The groups ids.
   */
  public function getGroupIdsByUserAndRole(AccountInterface $account, string $role_id): array;

  /**
   * Removes the user from all membership groups.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   */
  public function removeUserFromAllGroups(UserInterface $user): void;

  /**
   * Checks if content and member belong to the same groups.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Content node object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account object.
   *
   * @return bool
   *   TRUE if content and member belong to the same groups, FALSE otherwise.
   */
  public function hasSameGroups(NodeInterface $node, AccountInterface $account): bool;

  /**
   * Checks if content and member has at least one group in common for the same group scope. Logic is: AND for different group scope,
   * OR for same group scope.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Content node object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account object.
   *
   * @return bool
   *   TRUE if content and member belong to the same groups with the logic: AND for different group scope,
   *  OR for same group scope. FALSE otherwise.
   */
  public function hasSameGroupsInSameScope(NodeInterface $node, AccountInterface $account): bool;

  /**
   * Checks if content and member has at least one group in common.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Content node object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account object.
   *
   * @return bool
   *   TRUE if content and member has at least one group in common,
   *   FALSE otherwise.
   */
  public function hasGroupsInCommon(NodeInterface $node, AccountInterface $account): bool;

  /**
   * Find a group by name.
   *
   * @param string $name
   *   The group's name.
   *
   * @return \Drupal\group\Entity\Group|null
   *   The founded group or NULL if no group with this name exist.
   */
  public function findGroup(string $name): ?Group;

  /**
   * Return a list of groups to be added to the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user entity.
   * @param array $groups
   *   The final list of group a user must have.
   * @param bool $external
   *   True if the user is external for the HMRS system.
   * @param bool $manager
   *   True if the user is manager for the HMRS system.
   *
   * @return array
   *   The list of groups to add to the user.
   */
  public function getGroupsToAdd(UserInterface $user, array $groups, bool $external, bool $manager = FALSE);

  /**
   * Return a list of groups to be removed from the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user entity.
   * @param array $groups
   *   The final list of group a user must have.
   * @param bool $external
   *   True if the user is external for the HMRS system.
   * @param bool $manager
   *   True if the user is manager for the HMRS system.
   *
   * @return array
   *   The list of groups to remove from the user.
   */
  public function getGroupsToRemove(UserInterface $user, array $groups, bool $external, bool $manager = FALSE);

}
