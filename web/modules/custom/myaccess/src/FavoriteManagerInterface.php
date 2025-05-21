<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\user\UserInterface;

/**
 * Provides an interface for remove application in favorie.
 */
interface FavoriteManagerInterface {

  /**
   * Removes denied applications from favorites.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal User entity.
   * @param \Drupal\myaccess\Entity\Application[] $applications
   *   The user applications.
   */
  public function removeZombieApplications(UserInterface $user, array $applications): void;

  /**
   * If app id does not exist between applications it is removed from the flags.
   *
   * @param array $applications
   *   Array applications items.
   * @param int $app_id
   *   Application id.
   */
  public function removeApplication(array $applications, int $app_id);

}
