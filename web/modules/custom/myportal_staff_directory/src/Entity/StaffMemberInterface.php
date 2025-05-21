<?php

namespace Drupal\myportal_staff_directory\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Represents a StaffMember entity.
 */
interface StaffMemberInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Member name.
   *
   * @return string
   *   The member full name.
   */
  public function getName(): string;

  /**
   * Sets the member full name.
   *
   * @param string $first_name
   *   The member first name.
   *
   * @param string $last_name
   *   The member last name.
   *
   * @return \Drupal\myportal_staff_directory\StaffMemberInterface
   *   The called StaffMember entity.
   */
  public function setName(string $first_name, string $last_name): StaffMemberInterface;

  /**
   * Sets the Reporting field with links.
   *
   * @return string
   *   team leader links
   */
  public function setReportingToHtml(): StaffMemberInterface;

  /**
   * Sets the Reporting field with plain text email address.
   *
   * @return string
   *   team leader email address
   */
  public function setReportingToText(): StaffMemberInterface;

  /**
   * Sets the Team field with links.
   *
   * @return string
   *   team members links
   */
  public function setTeamToHtml(): StaffMemberInterface;

  /**
   * Gets the Member creation timestamp.
   *
   * @return int
   *   The created time.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the StaffMember creation timestamp.
   *
   * @param int $timestamp
   *   The created time.
   *
   * @return \Drupal\myportal_staff_directory\StaffMemberInterface
   *   The called StaffMember entity.
   */
  public function setCreatedTime($timestamp): StaffMemberInterface;

  /**
   * Gets the Employee field.
   *
   * @return string
   *   Employee type
   */
  public static function getEmployeeType(array $json_member): string;

  /**
   * Gets the legalentities allowed values.
   *
   * @return array<string>
   *   legalentities
   */
  public static function getRegionalLegalentities(): array;

}