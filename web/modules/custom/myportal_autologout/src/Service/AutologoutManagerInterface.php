<?php

namespace Drupal\myportal_autologout\Service;

use Drupal\user\UserInterface;

/**
 * Defines the AutologoutManagerInterface trait.
 *
 * @package Drupal\myportal_autologout\Service
 */
interface AutologoutManagerInterface {

  /**
   * Get the timer HTML markup.
   *
   * @return string
   *   HTML to insert a countdown timer.
   */
  public function createTimer();

  /**
   * Get the time remaining before logout.
   *
   * @return int
   *   Number of seconds remaining.
   */
  public function getRemainingTime();

  /**
   * Get a user's delay in seconds.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   (Optional) Provide a user's to get the delay for.
   *   Default is the logged in user.
   *
   * @return int
   *   The number of seconds before being active the autologout system.
   *   A value of 0 means no delay.
   */
  public function getUserDelay(UserInterface $user = NULL);

  /**
   * Get a user's logout URL.
   *
   * @param null|int $uid
   *   User id or NULL to use current logged in user.
   *
   * @return string
   *   User's logout URL.
   */
  public function getUserRedirectUrl($uid = NULL);

  /**
   * Get a user's timeout in seconds.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   (Optional) Provide a user's to get the timeout for.
   *   Default is the logged in user.
   *
   * @return int
   *   The number of seconds the user can be idle for before being logged out.
   *   A value of 0 means no timeout.
   */
  public function getUserTimeout(UserInterface $user = NULL);

  /**
   * Perform Logout.
   *
   * Helper to perform the actual logout. Destroys the session of the logged
   * in user.
   */
  public function logout();

  /**
   * Determine if autologout should be prevented.
   *
   * @return bool
   *   TRUE if there is a reason not to autologout
   *   the current user on the current page.
   */
  public function preventJs();

  /**
   * Determine if connection should be refreshed.
   *
   * @return bool
   *   TRUE if something about the current context should keep the connection
   *   open. FALSE and the standard countdown to autologout applies.
   */
  public function refreshOnly();

}
