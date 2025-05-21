<?php

//@msg_clean

namespace Drupal\myaccess;

use Drupal\Core\Session\AccountInterface;
use Drupal\myaccess\Entity\ApplicationInterface;
use Drupal\myaccess\Model\ExternalUser;
use Drupal\myaccess\Model\HmrsUserData;
use Drupal\user\UserInterface;
use Drupal\myaccess\OpenId\KeycloakAccessToken;

/**
 * Interface for User managers.
 */
interface UserManagerInterface {

  /**
   * Login the user.
   *
   * Register the user if not present on Drupal.
   *
   * @param \Drupal\myaccess\Model\ExternalUser $externalUser
   *   The user to login.
   */
  public function loginRegister(ExternalUser $externalUser, KeycloakAccessToken $access_token): void;

  /**
   * Return the current logged in user.
   *
   * @return \Drupal\user\UserInterface
   *   The current logged in user.
   */
  public function getCurrentDrupalUser(): UserInterface;

  /**
   * Get the user username.
   *
   * @return string
   *   The user username.
   */
  public function getUsername(): string;

  /**
   * Get the user password in plain text.
   *
   * @return string
   *   The user password in plain text.
   */
  public function getPassword(): string;

  /**
   * Return TRUE if the user is accessing from outside the Menarini network.
   *
   * @return bool
   *   TRUE if the user is accessing from outside the Menarini network.
   */
  public function isExternal(): bool;

  /**
   * Check if the user can access from outside the Menarini network.
   *
   * @return bool
   *   Return TRUE if the user can access from outside the Menarini network.
   */
  public function checkAccessExternal(): bool;

  /**
   * Attach applications to the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal User entity.
   * @param \Drupal\myaccess\Entity\Application[] $applications
   *   The user applications.
   */
  public function attachApplications(UserInterface $user, array $applications): void;

  /**
   * Check if an user has access to an application.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account.
   * @param \Drupal\myaccess\Entity\ApplicationInterface $application
   *   An application.
   *
   * @return bool
   *   TRUE if an user has access to an application.
   */
  public function hasApplication(AccountInterface $account, ApplicationInterface $application): bool;

  /**
   * Update user data from the Hmrs.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal User entity.
   * @param \Drupal\myaccess\Model\HmrsUserData|null $userData
   *   User data extracted from the HMRS system.
   *
   * @throws \Drupal\myaccess\Exception\LoginNotAllowedException
   * @throws \Drupal\myaccess\Exception\UpdateUserGroupsException
   */
  public function updateData(UserInterface $user, ?HmrsUserData $userData): void;

  /**
   * Update the user picture.
   *
   * @param string $picture
   *   The user's picture.
   */
  public function updateUserPicture(string $picture): void;

  /**
   * Retrieve the group Country of User.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account.
   * @param string $group_scope
   *   The group scope interested. See constants in GroupManagerInterface.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group or null if not found.
   */
  public function getGroupScopeForUser(AccountInterface $account, string $group_scope);

  /**
   * Retrieve the terms navigation that use is editor.
   *
   * Note: exclude the first and second level of tree terms.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account.
   *
   * @return array
   *   An array of terms id.
   */
  public function getTermsIdNavigationThatUserIsEditor(AccountInterface $account): array;

}
