<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

/**
 * Represent all Hmrs user data, aggregated.
 *
 * If a user has multiple positions (i.e. more than one line in the csv file),
 * this class will aggregate all the data from all the positions.
 */
class HmrsUserData {

  /**
   * A list of Hmrs data records.
   *
   * @var array
   */
  private array $records;

  /**
   * A list of position titles.
   *
   * @var string[]
   */
  private array $positionTitles;

  /**
   * TRUE if the user is external.
   *
   * @var bool
   */
  private bool $external;

  /**
   * TRUE if the user is manager.
   *
   * @var bool
   */
  private bool $manager;

  /**
   * TRUE if the position is the primary position.
   *
   * @var bool
   */
  private bool $primaryPos;

  /**
   * HmrsUserData constructor.
   *
   * @param array $records
   *   A list of Hmrs data records.
   * @param string[] $positionTitles
   *   A list of position titles.
   * @param bool $external
   *   TRUE if the user is external.
   * @param bool $manager
   *   TRUE if the user is manager.
   * @param bool $primaryPos
   *    TRUE if the position is the primary position.
   */
  public function __construct(array $records, array $positionTitles, bool $external, bool $manager, bool $primaryPos) {
    $this->records = $records;
    $this->positionTitles = $positionTitles;
    $this->external = $external;
    $this->manager = $manager;
    $this->primaryPos = $primaryPos;
  }

  /**
   * Return a list of Hmrs data records.
   *
   * @return array
   *   A list of Hmrs data records.
   */
  public function getRecords(): array {
    return $this->records;
  }

  /**
   * Return a list of position titles.
   *
   * @return string[]
   *   A list of position titles.
   */
  public function getPositionTitles(): array {
    return $this->positionTitles;
  }

  /**
   * Return TRUE if the user is external.
   *
   * @return bool
   *   TRUE if the user is external.
   */
  public function isExternal(): bool {
    return $this->external;
  }

  /**
   * Return TRUE if the user is manager.
   *
   * @return bool
   *   TRUE if the user is manager.
   */
  public function isManager(): bool {
    return $this->manager;
  }

  /**
   * Return TRUE if the position is the primary position.
   *
   * @return bool
   *   TRUE if the position is the primary position.
   */
  public function isPrimaryPosition(): bool {
    return $this->primaryPos;
  }

}
