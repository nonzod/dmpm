<?php

declare(strict_types=1);

namespace Drupal\myaccess\Exception;

/**
 * Exception thrown when the system cannot update the user groups.
 */
class UpdateUserGroupsException extends \Exception {

  /**
   * UpdateUserGroupsException constructor.
   *
   * @param \Exception $parent
   *   The parent exception.
   */
  public function __construct(\Exception $parent) {
    parent::__construct(
      sprintf('Exception updating user groups: %s', $parent->getMessage())
    );
  }

}
