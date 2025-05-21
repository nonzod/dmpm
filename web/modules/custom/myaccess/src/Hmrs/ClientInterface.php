<?php

declare(strict_types=1);

namespace Drupal\myaccess\Hmrs;

use Drupal\myaccess\Model\HmrsUserData;

/**
 * Interface for Hmrs clients.
 */
interface ClientInterface {

  /**
   * Return HMRS data for a user.
   *
   * @param string $email
   *   The user email address.
   * @param bool $onlyPrimary
   *    If get only data for primaryPosition
   *
   * @return \Drupal\myaccess\Model\HmrsUserData|null
   *   The HmrsUserData or null if the user was not found.
   *
   * @throws \Drupal\myaccess\Exception\UserDataRetrievalException
   */
  public function getUserData(string $email, bool $onlyPrimary = false): ?HmrsUserData;

  /**
   * Retrieve all user locations from API.
   *
   * @return array
   *   Return the all position in API.
   */
  public function getAllHierarchy(): array;

  /**
   * Build the HMRS user data from a set of HMRS user records.
   *
   * @param \Drupal\myaccess\Model\HmrsUserRecord[] $records
   *   A set of HMRS user records.
   *
   * @return \Drupal\myaccess\Model\HmrsUserData
   *   The HMRS user data.
   */
  public function buildUserData(array $records): HmrsUserData;

}
