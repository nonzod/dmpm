<?php

declare(strict_types=1);

namespace Drupal\myaccess\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event for user login.
 */
class UserLoginEvent extends Event {

  /**
   * A Drupal User.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * True if the user can continue with login, false otherwise.
   *
   * @var bool
   */
  private $loginAllowed;

  /**
   * UserLoginEvent constructor.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal User.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
    $this->loginAllowed = TRUE;
  }

  /**
   * Return the Drupal User.
   *
   * @return \Drupal\user\UserInterface
   *   A Drupal User.
   */
  public function getUser(): UserInterface {
    return $this->user;
  }

  /**
   * Return true if the user can continue with login, false otherwise.
   *
   * @return bool
   *   True if the user can continue with login, false otherwise.
   */
  public function isLoginAllowed(): bool {
    return $this->loginAllowed;
  }

  /**
   * Set to true if the user can continue with login, false otherwise.
   *
   * @param bool $loginAllowed
   *   True if the user can continue with login, false otherwise.
   */
  public function setLoginAllowed(bool $loginAllowed): void {
    $this->loginAllowed = $loginAllowed;
  }

}
