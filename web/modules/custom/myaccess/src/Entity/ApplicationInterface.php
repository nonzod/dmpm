<?php

declare(strict_types=1);

namespace Drupal\myaccess\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Interface for Application entities.
 */
interface ApplicationInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Return TRUE if this application can be added to favorites.
   *
   * @return bool
   *   TRUE if this application can be added to favorites.
   */
  public function hasFavorite(): bool;

  /**
   * Return the type of this application.
   *
   * @return string
   *   The type of this application.
   */
  public function getType(): string;

  /**
   * Return the application's settings.
   *
   * @return array
   *   The application's settings.
   */
  public function getSettings(): array;

  /**
   * Return the application url.
   *
   * @return string
   *   The application url.
   */
  public function getUrl(): string;

  /**
   * Return the application image url.
   *
   * @return mixed
   *   The application image url.
   */
  public function getImageUrl();

  /**
   * Return the application description, if any.
   *
   * @return string
   *   The application description.
   */
  public function getDescription(): string;

  /**
   * Return an array of group's ids this application is in.
   *
   * Return an empty array if this application is in a bundle without group
   * access information.
   *
   * @return int[]
   *   An array of group's ids this application is in.
   */
  public function getGroups(): array;

  /**
   * Retrieve the visibility of application (used for remote application).
   *
   * @return string
   *   Can contain 'public' or 'private'.
   */
  public function getVisibility(): string;

}
