<?php

namespace Drupal\myaccess\Event;

use Drupal\user\UserInterface;
use SocialConnect\Provider\AccessTokenInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the UserPostReinsertPasswordEvent class.
 *
 * @package Drupal\myaccess\Event
 */
class UserPostReinsertPasswordEvent extends Event {

  /**
   * A Drupal User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * An Access token retrieve for user.
   *
   * @var \SocialConnect\Provider\AccessTokenInterface|null
   */
  protected $accessToken;

  /**
   * Construct new UserPostReinsertPasswordEvent instance.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user target.
   * @param \SocialConnect\Provider\AccessTokenInterface|null $access_token
   *   The access token found for user.
   */
  public function __construct(UserInterface $user, ?AccessTokenInterface $access_token) {
    $this->user = $user;
    $this->accessToken = $access_token;
  }

  /**
   * Retrieve the user.
   *
   * @return \Drupal\user\UserInterface
   *   The user object.
   */
  public function getUser(): UserInterface {
    return $this->user;
  }

  /**
   * Retrieve the access token.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface|null
   *   The access token. Null if not found.
   */
  public function getAccessToken(): ?AccessTokenInterface {
    return $this->accessToken;
  }

}
