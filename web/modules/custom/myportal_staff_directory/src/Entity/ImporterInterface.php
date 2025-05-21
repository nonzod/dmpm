<?php

namespace Drupal\myportal_staff_directory\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Importer configuration entity.
 */
interface ImporterInterface extends ConfigEntityInterface {

  /**
   * Returns the Url where the import can get the data from.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getUrl();

  /**
   * Returns the Importer plugin ID to be used by this importer.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId();

  /**
   * Returns the backups retention days.
   *
   * @return string
   *   The backups retention days.
   */
  public function getRetentionDays();

}
