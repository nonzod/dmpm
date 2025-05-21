<?php

namespace Drupal\myportal_localadmin\Manager;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserStorageInterface;

/**
 *    
 * group_content__group_roles
 * bundle = "flexible_group-group_membership"
 * deleted = 0
 * entity_id = $member->id()
 * revision_id = $member->id()
 * langcode = user lang
 * delta = 0
 * group_roles_target_id = 'flexible_group-content_editor' || 'flexible_group-editor'
 */
class LocalAdminGroupRoleManager {
  /**
   * User language
   * 
   * @var string
   */
  protected string $language;

  /**
   * Database connetion
   * 
   * @var Connection
   */
  protected Connection $dbConnection;

  /**
   * Target group table
   * 
   * @var string
   */
  protected string $dbTable = 'group_content__group_roles';

  /**
   * Target bundle
   * 
   * @var string
   */
  protected string $bundle = 'flexible_group-group_membership';

  /**
   * Allowed roles for Local Admin
   * 
   * @var array
   */
  protected array $allowedRoles = ['flexible_group-content_editor', 'flexible_group-editor'];

  /**
   * Current group entity entity
   * 
   * @var GroupInterface
   */
  protected GroupInterface $group;

  /**
   * Current group member content entity
   * This is not actually the user but the Group Content that represents the user within the group.
   * Its "entity_id" is unique and is different for each group to which it belongs.
   * 
   * @var GroupContentInterface
   */
  protected GroupContentInterface $member;

  /**
   * User storage
   * 
   * @var UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * Constructor
   */
  public function __construct(Connection $database) {
    $this->dbConnection = $database;
    $this->userStorage = \Drupal::entityTypeManager()->getStorage('user');
  }

  /**
   * Set current group entity
   */
  public function setGroup(GroupInterface $group) {
    $this->group = $group;
  }

  /**
   * Set current group member entity
   */
  public function setMember(GroupContentInterface $member) {
    $this->member = $member;

    $user = $this->userStorage->load($member->get('uid')->getString());
    $this->language = $user->get('langcode')->getString();
  }

  /**
   * Allowed roles
   * 
   * @return array
   */
  public function getAllowedRoles() {
    return $this->allowedRoles;
  }

  /**
   * List of group roles for current member
   * 
   * @return array
   */
  public function getGroupRoles() {
    $out = [];
    $query = $this->dbConnection->select($this->dbTable, 'gr', []);
    $query->fields('gr', ['group_roles_target_id'])
      ->condition('gr.bundle', $this->bundle)
      ->condition('gr.entity_id', $this->member->id());
    $roles = $query->execute()->fetchAll();

    foreach ($roles as $role) {
      $out[] = $role->group_roles_target_id;
    }

    return $out;
  }

  /**
   * Check if current user has role
   * 
   * @return bool
   */
  public function hasGroupRole(string $role_id) {
    $query = $this->dbConnection->select($this->dbTable, 'gr', []);
    $query->fields('gr', ['entity_id'])
      ->condition('gr.bundle', $this->bundle)
      ->condition('gr.entity_id', $this->member->id())
      ->condition('gr.group_roles_target_id', $role_id);

    $count = $query->countQuery()->execute()->fetchField();

    return intval($count) > 0 ? TRUE : FALSE;
  }

  /**
   * Grant group role to member
   */
  public function grantGroupRole(string $role_id) {
    $trans = $this->dbConnection->startTransaction();
    try {
      $entity_id = $this->member->id();
      $delta = $this->getDelta($entity_id);
      return $this->dbConnection->insert($this->dbTable)
        ->fields([
          'bundle' => $this->bundle,
          'deleted' => 0,
          'entity_id' => $entity_id,
          'revision_id' => $entity_id,
          'langcode' => $this->language,
          'delta' => $delta,
          'group_roles_target_id' => $role_id
        ])->execute();
    } catch (\Exception $e) {
      $trans->rollBack();
      \Drupal::logger('localadmin')->error($e->getMessage());
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  /**
   * Revoke group role to member
   */
  public function revokeGroupRole(string $role_id) {
    $trans = $this->dbConnection->startTransaction();
    try {
      $entity_id = $this->member->id();
      return $this->dbConnection->delete($this->dbTable)
        ->condition('bundle', $this->bundle)
        ->condition('entity_id', $entity_id)
        ->condition('group_roles_target_id', $role_id)
        ->execute();
    } catch (\Exception $e) {
      $trans->rollBack();
      \Drupal::logger('localadmin')->error($e->getMessage());
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  /**
   * Get role delta because is part of unique key
   * 
   * @return int
   */
  public function getDelta() {
    $entity_id = $this->member->id();
    $query = $this->dbConnection->select($this->dbTable, 'gr', []);
    $query->fields('gr', ['delta'])
      ->condition('gr.bundle', $this->bundle)
      ->condition('gr.entity_id', $entity_id);
    $delta = $query->execute()->fetchField();

    return $delta !== false ? intval($delta) + 1  : 0;
  }
}