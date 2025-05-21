<?php

declare(strict_types=1);

namespace Drupal\myaccess\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\myaccess\Access\ApplicationAccessResult;

/**
 * Defines an implementation for entity access control handler.
 */
class ApplicationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return ApplicationAccessResult::allowedIfEnabledForUser($account, $entity);
    }

    return AccessResult::forbidden();
  }

}
