<?php

//@msg_clean

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\myaccess\Exception\GroupNotCreatedException;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines the GroupManager class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @package Drupal\myaccess
 */
class GroupManager implements GroupManagerInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The Group membership service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected GroupMembershipLoaderInterface $groupMembership;

  /**
   * The Group storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $groupStorage;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * GroupManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $group_storage
   *   The Group storage service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership
   *   The Group membership service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(
    EntityStorageInterface $group_storage,
    GroupMembershipLoaderInterface $group_membership,
    LoggerInterface $logger,
    CacheBackendInterface $cache_backend
  ) {
    $this->groupStorage = $group_storage;
    $this->groupMembership = $group_membership;
    $this->logger = $logger;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Create a group with name, a scope and some contexts.
   *
   * @param string $name
   *   The group's name.
   * @param string $scope
   *   The group scope (functional or geographic).
   * @param string[] $context
   *   The group context (content or mylinks).
   *
   * @return \Drupal\group\Entity\Group
   *   The new Group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createGroup(string $name, string $scope, array $context): Group {
    /** @var \Drupal\group\Entity\Group $group */
    $group = $this->groupStorage->create([
      'type' => 'flexible_group',
      'uid' => 1,
      'label' => $name,
      'field_flexible_group_visibility' => 'members',
      'field_group_allowed_visibility' => ['public', 'community', 'group'],
      'field_group_allowed_join_method' => 'added',
      'field_group_scope' => $scope,
      /* @see MyPortalGroupSelectorWidget::alterGroupsByContext */
      'field_group_context' => $context,
    ]);

    $group->enforceIsNew();
    $group->save();

    $this->logger->info('Created group "@group" with scope "@scope" in context "@context".', [
      '@group' => $name,
      '@scope' => $scope,
      '@context' => $context,
    ]);

    return $group;
  }

  /**
   * {@inheritDoc}
   */
  public function createIfNotExists(string $name, string $scope, array $context): Group {
    if ($name == '') {
      throw new GroupNotCreatedException($name, $scope, $context, 'Group name is empty.');
    }

    // Check if the context array contains valid values.
    $valid_context = array_reduce($context, function ($carry, string $item) {
      return in_array($item, [
        GroupManagerInterface::CONTEXT_MYLINKS,
        GroupManagerInterface::CONTEXT_CONTENT,
      ]);
    }, FALSE);
    if (!$valid_context) {
      throw new GroupNotCreatedException($name, $scope, $context, 'Invalid context.');
    }

    try {
      if ($existing = $this->findGroup($name)) {
        $this->logger->info('Group @name exists, skipping.', ['@name' => $name]);

        return $this->updateExistingGroup($existing, $scope, $context);
      }
      else {
        return $this->createGroup($name, $scope, $context);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('GroupManager service throw exception in "createIfNotExists": @message.', [
        '@message' => $e->getMessage(),
      ]);

      throw new GroupNotCreatedException($name, $scope, $context, $e->getMessage());
    }
  }

  /**
   * {@inheritDoc}
   */
  public function addUserToGroup(UserInterface $user, string $group_name): void {
    $group = $this->findGroup($group_name);
    if ($group == NULL) {
      return;
    }

    try {
      $group->addMember($user, ['group_roles' => []]);
      $group->save();

      $this->logger->info('Added user "@account" to group "@group".', [
        '@account' => $user->getAccountName(),
        '@group' => $group_name,
      ]);
    }
    catch (EntityStorageException $e) {
      $this->logger->error('GroupManager service throw exception in "addUserToGroup": @message.', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function removeUserFromGroup(UserInterface $user, $group_name): void {
    $group = $this->findGroup($group_name);
    if ($group == NULL) {
      return;
    }

    try {
      $group->removeMember($user);
      $group->save();

      $this->logger->info('Removed user "@name" from group "@group".', [
        '@name' => $user->getAccountName(),
        '@group' => $group_name,
      ]);
    }
    catch (EntityStorageException $e) {
      $this->logger->error('GroupManager service throw exception in "removeUserFromGroup": @message.', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupIdsByNode(NodeInterface $node): array {
    $group_ids = [];

    $cid = 'myaccess:group_manager:getGroupIdsByNode:' . $node->id();
    if ($cache = $this->cacheBackend->get($cid)) {
      $group_ids = $cache->data;
    }
    else {
      $tags = $node->getCacheTags();

      $group_contents = GroupContent::loadByEntity($node);
      foreach ($group_contents as $group_content) {
        $group = $group_content->getGroup();
        $group_ids[] = $group->id();

        $tags = Cache::mergeTags($tags, $group->getCacheTags());
      }

      $this->cacheBackend->set($cid, $group_ids, Cache::PERMANENT, $tags);
    }

    return $group_ids;
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupIdsByUser(AccountInterface $account): array {
    $ids = [];

    $cid = 'myaccess:group_manager:getGroupIdsByUser:' . $account->id();
    $cache = $this->cacheBackend->get($cid);
    if ($cache !== FALSE && !empty($cache->data)) {
      $ids = $cache->data;
    }
    else {
      $tags = ['user:' . $account->id()];

      foreach ($this->groupMembership->loadByUser($account) as $membership) {
        $group = $membership->getGroup();
        $ids[] = $group->id();

        $tags = Cache::mergeTags($tags, $membership->getCacheTags());
        $tags = Cache::mergeTags($tags, $group->getCacheTags());
      }

      $this->cacheBackend->set($cid, $ids, Cache::PERMANENT, $tags);
    }

    return $ids;
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupIdsByUserAndRole(AccountInterface $account, string $role_id): array {
    $ids = [];

    $cid = 'myaccess:group_manager:getGroupIdsByUserAndRoles:' . $account->id() . ':' . $role_id;
    $cache = $this->cacheBackend->get($cid);
    if ($cache !== FALSE && !empty($cache->data)) {
      $ids = $cache->data;
    }
    else {
      $tags = ['user:' . $account->id()];

      foreach ($this->groupMembership->loadByUser($account) as $membership) {

        $roles_id = [];
        $roles = $membership->getRoles();
        array_walk($roles, function ($role) use (&$roles_id) {
          assert($role instanceof GroupRoleInterface);
          $role_id = str_replace($role->getGroupTypeId() . "-", '', $role->id());
          $roles_id[$role_id] = $role_id;
        });

        if (!in_array($role_id, $roles_id)) {
          continue;
        }

        $group = $membership->getGroup();
        $ids[] = $group->id();

        $tags = Cache::mergeTags($tags, $membership->getCacheTags());
        $tags = Cache::mergeTags($tags, $group->getCacheTags());
      }

      $this->cacheBackend->set($cid, $ids, Cache::PERMANENT, $tags);
    }

    return $ids;
  }

  /**
   * {@inheritDoc}
   */
  public function removeUserFromAllGroups(UserInterface $user): void {
    // Don't remove groups if the user has an administrative role.
    if ($user->hasRole('administrator')) {
      return;
    }

    // Get groups for current users.
    $groups_id = $this->getGroupIdsByUser($user);

    $groups = $this->groupStorage->loadMultiple($groups_id);

    /** @var \Drupal\group\Entity\Group $group */
    foreach ($groups as $group) {
      $group->removeMember($user);
      $this->logger->info('Removed user "@name" from group "@group".', [
        '@user' => $user->getAccountName(),
        '@group' => $group->label() ?? '-noname-',
      ]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function hasSameGroups(NodeInterface $node, AccountInterface $account): bool {
    $content_ids = $this->getGroupIdsByNode($node);
    $membership_ids = $this->getGroupIdsByUser($account);

    return empty(array_diff($content_ids, $membership_ids));
  }

  /**
   * {@inheritDoc}
   */
  public function hasSameGroupsInSameScope(NodeInterface $node, AccountInterface $account): bool {
    $content_ids = $this->getGroupIdsByNode($node);
    $membership_ids = $this->getGroupIdsByUser($account);

    $hasAccess = false;
    $groupsScopes = array();

    //@TODO: snellire
    //ciclo tra i gruppi del nodo per raggrupparli in altro array secondo il group_scope
    foreach ($content_ids as $singleGroup) {
      $group = Group::load($singleGroup);
      $groupScopeField = $group->get('field_group_scope')->getValue()[0]['value'];
      $groupsScopes[$groupScopeField][] = $singleGroup;
    }

    //dopodiché per ogni group_scope faccio il match con i gruppi dell'utente; così facendo se è un solo gruppo è OR, altrimenti diventa AND
    foreach($groupsScopes as $keyarray => $value) {
      $result = array_intersect($value, $membership_ids);
      if(!empty($result)) {
        $hasAccess = true;
      } else {
        $hasAccess = false;
        break;
      }
    }

    return $hasAccess;
  }

  /**
   * {@inheritDoc}
   */
  public function hasGroupsInCommon(NodeInterface $node, AccountInterface $account): bool {
    $content_ids = $this->getGroupIdsByNode($node);
    $membership_ids = $this->getGroupIdsByUser($account);

    return !empty(array_intersect($content_ids, $membership_ids));
  }

  /**
   * {@inheritDoc}
   */
  public function findGroup(string $name): ?Group {
    try {

      /** @var \Drupal\group\Entity\Group[] $group */
      $group = $this->groupStorage->loadByProperties(['label' => $name]);
      $group = reset($group);

      if ($group) {
        return $group;
      }
      else {
        return NULL;
      }
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupsToAdd(UserInterface $user, array $groups, bool $external, bool $manager = FALSE): array {
    $userGroup = [];
    foreach ($this->groupMembership->loadByUser($user) as $membership) {
      $label = $membership->getGroup()->label();
      if ($label != NULL) {
        $userGroup[] = ['name' => $label, 'scope' => ''];
      }
    }

    $groups[] = [
      'name' => GroupManagerInterface::GLOBAL,
      'scope' => 'country',
    ];
    $groups[] = [
      'name' => $external? GroupManagerInterface::EXTERNAL: GroupManagerInterface::INTERNAL,
      'scope' => '',
    ];
    $groups[] = [
      'name' => $manager? GroupManagerInterface::MANAGER: GroupManagerInterface::NO_MANAGER,
      'scope' => '',
    ];

    return array_udiff($groups, $userGroup, function (array $group1, array $group2) {
      return strtolower((string) $group1['name']) <=> strtolower((string) $group2['name']);
    });
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupsToRemove(UserInterface $user, array $groups, bool $external, $manager = FALSE): array {
    $userGroup = [];
    foreach ($this->groupMembership->loadByUser($user) as $membership) {
      $label = $membership->getGroup()->label();
      if ($label != NULL) {
        $userGroup[] = ['name' => $label, 'scope' => ''];
      }
    }

    $groups[] = [
      'name' => GroupManagerInterface::GLOBAL,
      'scope' => 'country',
    ];
    $groups[] = [
      'name' => $external? GroupManagerInterface::EXTERNAL: GroupManagerInterface::INTERNAL,
      'scope' => '',
    ];
    $groups[] = [
      'name' => $manager? GroupManagerInterface::MANAGER: GroupManagerInterface::NO_MANAGER,
      'scope' => '',
    ];

    return array_udiff($userGroup, $groups, function (array $group1, array $group2) {
      return strtolower((string) $group1['name']) <=> strtolower((string) $group2['name']);
    });
  }

  /**
   * Update the scope and context of an existing group.
   *
   * @param \Drupal\group\Entity\Group $group
   *   A group.
   * @param string $scope
   *   The group scope (functional or geographic).
   * @param array $context
   *   The group context (content or mylinks).
   *
   * @return \Drupal\group\Entity\Group
   *   The updated Group.
   */
  private function updateExistingGroup(Group $group, string $scope, array $context): Group {
    $old_context = $group->get('field_group_context')->getValue();
    $new_context = array_merge($old_context, $context);

    try {
      $group->set('field_group_scope', $scope);
      $group->set('field_group_context', $new_context);
      $group->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Error while updating group %name: %error.', [
        '%name' => ($group->label() ?? 'unknown'),
        '%error' => $e->getMessage(),
      ]);
    }

    return $group;
  }

}
