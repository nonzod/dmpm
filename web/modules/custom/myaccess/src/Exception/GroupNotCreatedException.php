<?php

declare(strict_types=1);

namespace Drupal\myaccess\Exception;

/**
 * Exception thrown when a group cannot be created.
 */
class GroupNotCreatedException extends \Exception {

  /**
   * GroupNotCreatedException constructor.
   *
   * @param string $name
   *   The name of the group to create.
   * @param string $scope
   *   The group scope (functional or geographic).
   * @param string[] $context
   *   The group context (content or mylinks).
   * @param string $error
   *   The parent error message.
   */
  public function __construct(string $name, string $scope, array $context, string $error) {
    parent::__construct(
      sprintf(
        'Group "%s" with scope: "%s" and context: "%s" cannot be created. Error: %s',
        $name,
        $scope,
        implode(', ', $context),
        $error
      )
    );
  }

}
