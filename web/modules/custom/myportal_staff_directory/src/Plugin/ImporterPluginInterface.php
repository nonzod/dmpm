<?php

namespace Drupal\myportal_staff_directory\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\myportal_staff_directory\Entity\BackupInterface;

/**
 * Defines an interface for Importer plugins.
 */
interface ImporterPluginInterface extends PluginInspectionInterface {

  /**
   * Performs the import.
   *
   * @return bool
   *   Whether the import was successful.
   */
  public function import();

  /**
   * Performs the restore.
   *
   * @return bool
   *   Whether the import was successful.
   */
  public function restoreBackup(BackupInterface &$entity);

  /**
   * Returns the Importer configuration entity.
   *
   * @return \Drupal\myportal_staff_directory\Entity\ImporterInterface
   *   The importer config entity used by this plugin.
   */
  public function getConfig();

}
