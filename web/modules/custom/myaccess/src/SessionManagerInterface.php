<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\myaccess\Model\SessionData;

/**
 * Interface for Session managers.
 */
interface SessionManagerInterface {

  /**
   * Return all data about MyAccess stored in the session.
   *
   * @return \Drupal\myaccess\Model\SessionData
   *   All data about MyAccess stored in the session.
   */
  public function getAll(): SessionData;

  /**
   * Save the MyAccess session data to the session.
   *
   * @param \Drupal\myaccess\Model\SessionData $data
   *   The data to save.
   */
  public function save(SessionData $data): void;

}
