<?php

namespace Drupal\myportal_staff_directory\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Represents a Backup entity.
 */
interface BackupInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Backup creation timestamp.
   *
   * @return int
   *   The created time.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Backup creation timestamp.
   *
   * @param int $timestamp
   *   The created time.
   *
   * @return \Drupal\myportal_staff_directory\BackupInterface
   *   The called Backup entity.
   */
  public function setCreatedTime($timestamp): BackupInterface;
}
