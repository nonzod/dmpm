<?php

namespace Drupal\myaccess\Access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\myaccess\Entity\ApplicationInterface;

/**
 * Extends the AccessResult class with application permission checks.
 */
abstract class ApplicationAccessResult extends AccessResult {

  /**
   * Allows access if the permission is present, neutral otherwise.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permission, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  public static function allowedIfEnabledForUser(AccountInterface $account, EntityInterface $entity) {
    assert($entity instanceof ApplicationInterface);

    /** @var \Drupal\myaccess\UserManagerInterface $user_manager */
    $user_manager = \Drupal::service('myaccess.user_manager');

    return static::allowedIf($user_manager->hasApplication($account, $entity));
  }

}
