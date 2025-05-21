<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

use SocialConnect\Common\Entity\User as SocialConnectUser;

/**
 * Value object for external user data.
 */
class ExternalUser {

  /**
   * The user external id.
   *
   * @var string
   */
  private $id;

  /**
   * The user first name.
   *
   * @var string
   */
  private $firstname;

  /**
   * The user last name.
   *
   * @var string
   */
  private $lastname;

  /**
   * The user email address.
   *
   * @var string
   */
  private $email;

  /**
   * The user full username.
   *
   * @var string
   */
  private $username;

  /**
   * The user full name (first name and last name).
   *
   * @var string
   */
  private $fullname;

  /**
   * The url of the user picture file.
   *
   * @var string|null
   */
  private $pictureUrl;

  /**
   * The user resource access.
   *
   * @var array
   */
  private $resourceAccess;

  /**
   * The user roles.
   *
   * @var array
   */
  private $roles;

  /**
   * Convert a SocialConnect identity to a MyAccess user.
   *
   * @param \SocialConnect\Common\Entity\User $identity
   *   The SocialConnect identity.
   *
   * @return \Drupal\myaccess\Model\ExternalUser
   *   A MyAccess user.
   */
  final public static function fromIdentity(
    SocialConnectUser $identity
  ): self {
    $user = new ExternalUser();

    $user->id = $identity->id;
    $user->firstname = $identity->firstname;
    $user->lastname = $identity->lastname;
    $user->email = $identity->email;
    $user->username = $identity->username ?? $identity->email;
    $user->fullname = $identity->fullname ?? sprintf('%s %s', $identity->firstname, $identity->lastname);
    $user->pictureUrl = $identity->pictureURL;
    $user->resourceAccess = [];
    $user->roles = [];

    return $user;
  }

  /**
   * Return the user external id.
   *
   * @return string
   *   The user external id.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Return the user first name.
   *
   * @return string
   *   The user first name.
   */
  public function getFirstname(): ?string {
    return $this->firstname;
  }

  /**
   * Return the user last name.
   *
   * @return string
   *   The user last name.
   */
  public function getLastname(): ?string {
    return $this->lastname;
  }

  /**
   * Return the user email address.
   *
   * @return string
   *   The user email address.
   */
  public function getEmail(): string {
    return $this->email;
  }

  /**
   * Return the user full username.
   *
   * @return string
   *   The user full username.
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * Return the user full name (first name and last name).
   *
   * @return string
   *   The user full name (first name and last name).
   */
  public function getFullname(): ?string {
    return $this->fullname;
  }

  /**
   * Return the url of the user picture file.
   *
   * @return string|null
   *   The url of the user picture file.
   */
  public function getPictureUrl(): ?string {
    return $this->pictureUrl;
  }

  /**
   * Return the user resource access.
   *
   * @return array
   *   The user resource access.
   */
  public function getResourceAccess(): array {
    return $this->resourceAccess;
  }

  /**
   * Return the user roles.
   *
   * @return array
   *   The user roles.
   */
  public function getRoles(): array {
    return $this->roles;
  }

}
