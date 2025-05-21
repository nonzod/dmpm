<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\user\UserInterface;

/**
 * Provides an interface for entity applications managers.
 */
interface ApplicationsManagerInterface {

  /**
   * Save or update a list of applications.
   *
   * @param \Drupal\myaccess\Model\ExternalApplication[] $externalApplications
   *   The applications data from the external service.
   *
   * @return \Drupal\myaccess\Entity\Application[]
   *   Array of Application entities.
   */
  public function saveOrUpdate(array $externalApplications): array;

  /**
   * Return all the user's applications.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal User entity.
   *
   * @return array
   *   The user applications, as entity IDs.
   */
  public function getUserApplications(UserInterface $user): array;

  /**
   * Return the local applications.
   *
   * @return array
   *   The user applications, as entity IDs.
   */
  public function getLocalApplications(): array;

  /**
   * Return the list of Google applications.
   *
   * @return array
   *   The user applications, as entity IDs.
   */
  public function getGoogleApplications(): array;

  /**
   * Return an array with application data used for filtering.
   *
   * @param int $application_id
   *   An application id.
   *
   * @return array
   *   Array with application data used for filtering.
   */
  public function toSearchArray(int $application_id): array;

  /**
   * Return the list of MyLinks applications for current user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A Drupal User entity.
   *
   * @return array
   *   The user applications, as entity IDs.
   */
  public function getMyLinksApplications(UserInterface $user): array;

  /**
   * Get a list of favorite applications for a user.
   *
   * @return array
   *   The user applications, as entity IDs.
   */
  public function getFavoriteApplicationIds(): array;

  /**
   * Get a list of sorted applications for a user from the user data service.
   *
   * @return array
   *   The user applications, as entity IDs.
   */
  public function getSortedFavoriteApplicationIds(): array;

  /**
   * Set a list of sorted applications for a user from the user data service.
   *
   * @param array $sorted_application_ids
   *   An associative array containing the sorted favorite application IDs.
   */
  public function setSortedFavoriteApplicationIds(array $sorted_application_ids): void;

}
