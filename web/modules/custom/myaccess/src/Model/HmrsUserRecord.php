<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

/**
 * Represent an Hmrs User data record (i.e. a line in the csv file).
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class HmrsUserRecord {

  /**
   * The user globalEmployeeCode.
   *
   * @var string
   */
  private $globalEmployeeCode;

  /**
   * If the user is external.
   *
   * @var bool
   */
  private $external;

  /**
   * If the user is manager.
   *
   * @var bool
   */
  private $manager;

  /**
   * The title of the user.
   *
   * @var string
   */
  private $positionTitle;

  /**
   * The user company.
   *
   * @var string
   */
  private $company;

  /**
   * The user division.
   *
   * @var string
   */
  private $division;

  /**
   * The user department.
   *
   * @var string
   */
  private $department;

  /**
   * The user subArea.
   *
   * @var string
   */
  private $subArea;

  /**
   * The user subArea2.
   *
   * @var string
   */
  private $subArea2;

  /**
   * The user subArea3.
   *
   * @var string
   */
  private $subArea3;

  /**
   * The user subArea4.
   *
   * @var string
   */
  private $subArea4;

  /**
   * The user subArea5.
   *
   * @var string
   */
  private $subArea5;

  /**
   * The user subArea6.
   *
   * @var string
   */
  private $subArea6;

  /**
   * The user subArea7.
   *
   * @var string
   */
  private $subArea7;

  /**
   * The user function.
   *
   * @var string
   */
  private $function;

  /**
   * The user subFunction.
   *
   * @var string
   */
  private $subFunction;

  /**
   * The user legalEntity.
   *
   * @var string
   */
  private $legalEntity;

  /**
   * The user region.
   *
   * @var string
   */
  private $region;

  /**
   * The user country.
   *
   * @var string
   */
  private $country;

  /**
   * The user subRegion.
   *
   * @var string
   */
  private $subRegion;

  /**
   * The user location.
   *
   * @var string
   */
  private $location;

  /**
   * The user functionalArea.
   *
   * @var string
   */
  private $functionalArea;

  /**
   * The position area code.
   *
   * @var string
   */
  private $areaCode;

  /**
   * If the position is primary.
   *
   * @var bool
   */
  private $primaryPos;

  /**
   * HmrsUserData constructor.
   *
   * @param string $globalEmployeeCode
   *   The user globalEmployeeCode.
   * @param bool $external
   *   True if the user is external.
   * @param bool $manager
   *   True if the user is manager.
   * @param string $positionTitle
   *   The title of the user.
   * @param string $company
   *   The user company.
   * @param string $division
   *   The user division.
   * @param string $department
   *   The user department.
   * @param string $subArea
   *   The user subArea.
   * @param string $subArea2
   *   The user subArea2.
   * @param string $subArea3
   *   The user subArea3.
   * @param string $subArea4
   *   The user subArea4.
   * @param string $subArea5
   *   The user subArea5.
   * @param string $subArea6
   *   The user subArea6.
   * @param string $subArea7
   *   The user subArea7.
   * @param string $function
   *   The user function.
   * @param string $subFunction
   *   The user subFunction.
   * @param string $legalEntity
   *   The user legalEntity.
   * @param string $region
   *   The user region.
   * @param string $country
   *   The user country.
   * @param string $subRegion
   *   The user subRegion.
   * @param string $location
   *   The user location.
   * @param string $functionalArea
   *   The user functionalArea.
   * @param string $areaCode
   *   The position area code.
   * @param bool $primaryPos
   *   True if the position is the primary position.
   */
  public function __construct(
    string $globalEmployeeCode,
    bool $external,
    bool $manager,
    string $positionTitle,
    string $company,
    string $division,
    string $department,
    string $subArea,
    string $subArea2,
    string $subArea3,
    string $subArea4,
    string $subArea5,
    string $subArea6,
    string $subArea7,
    string $function,
    string $subFunction,
    string $legalEntity,
    string $region,
    string $country,
    string $subRegion,
    string $location,
    string $functionalArea,
    string $areaCode,
    bool $primaryPos
  ) {
    $this->globalEmployeeCode = $globalEmployeeCode;
    $this->external = $external;
    $this->manager = $manager;
    $this->positionTitle = $positionTitle;
    $this->company = $company;
    $this->division = $division;
    $this->department = $department;
    $this->subArea = $subArea;
    $this->subArea2 = $subArea2;
    $this->subArea3 = $subArea3;
    $this->subArea4 = $subArea4;
    $this->subArea5 = $subArea5;
    $this->subArea6 = $subArea6;
    $this->subArea7 = $subArea7;
    $this->function = $function;
    $this->subFunction = $subFunction;
    $this->legalEntity = $legalEntity;
    $this->region = $region;
    $this->country = $country;
    $this->subRegion = $subRegion;
    $this->location = $location;
    $this->functionalArea = $functionalArea;
    $this->areaCode = $areaCode;
    $this->primaryPos = $primaryPos;
  }

  /**
   * Return the user globalEmployeeCode.
   *
   * @return string
   *   The user globalEmployeeCode.
   */
  public function getGlobalEmployeeCode(): string {
    return $this->globalEmployeeCode;
  }

  /**
   * Return true if the user is external.
   *
   * @return bool
   *   True if the user is external.
   */
  public function isExternal(): bool {
    return $this->external;
  }

  /**
   * Return true if the user is manager.
   *
   * @return bool
   *   True if the user is manager.
   */
  public function isManager(): bool {
    return $this->manager;
  }

  /**
   * Return the title of the user.
   *
   * @return string
   *   The title of the user.
   */
  public function getPositionTitle(): string {
    return $this->positionTitle;
  }

  /**
   * Return the user company.
   *
   * @return string
   *   The user company.
   */
  public function getCompany(): string {
    return $this->company;
  }

  /**
   * Return the user division.
   *
   * @return string
   *   The user division.
   */
  public function getDivision(): string {
    return $this->division;
  }

  /**
   * Return the user department.
   *
   * @return string
   *   The user department.
   */
  public function getDepartment(): string {
    return $this->department;
  }

  /**
   * Return the user subArea.
   *
   * @return string
   *   The user subArea.
   */
  public function getSubArea(): string {
    return $this->subArea;
  }

  /**
   * Return the user subArea2.
   *
   * @return string
   *   The user subArea2.
   */
  public function getSubArea2(): string {
    return $this->subArea2;
  }

  /**
   * Return the user subArea3.
   *
   * @return string
   *   The user subArea3.
   */
  public function getSubArea3(): string {
    return $this->subArea3;
  }

  /**
   * Return the user subArea4.
   *
   * @return string
   *   The user subArea4.
   */
  public function getSubArea4(): string {
    return $this->subArea4;
  }

  /**
   * Return the user subArea5.
   *
   * @return string
   *   The user subArea5.
   */
  public function getSubArea5(): string {
    return $this->subArea5;
  }

  /**
   * Return the user subArea6.
   *
   * @return string
   *   The user subArea6.
   */
  public function getSubArea6(): string {
    return $this->subArea6;
  }

  /**
   * Return the user subArea7.
   *
   * @return string
   *   The user subArea7.
   */
  public function getSubArea7(): string {
    return $this->subArea7;
  }

  /**
   * Return the user function.
   *
   * @return string
   *   The user function.
   */
  public function getFunction(): string {
    return $this->function;
  }

  /**
   * Return the user subFunction.
   *
   * @return string
   *   The user subFunction.
   */
  public function getSubFunction(): string {
    return $this->subFunction;
  }

  /**
   * Return the user legalEntity.
   *
   * @return string
   *   The user legalEntity.
   */
  public function getLegalEntity(): string {
    return $this->legalEntity;
  }

  /**
   * Return the user region.
   *
   * @return string
   *   The user region.
   */
  public function getRegion(): string {
    return $this->region;
  }

  /**
   * Return the user country.
   *
   * @return string
   *   The user country.
   */
  public function getCountry(): string {
    return $this->country;
  }

  /**
   * Return the user subRegion.
   *
   * @return string
   *   The user subRegion.
   */
  public function getSubRegion(): string {
    return $this->subRegion;
  }

  /**
   * Return the user location.
   *
   * @return string
   *   The user location.
   */
  public function getLocation(): string {
    return $this->location;
  }

  /**
   * Return the user functionalArea.
   *
   * @return string
   *   The user functionalArea.
   */
  public function getFunctionalArea(): string {
    return $this->functionalArea;
  }

  /**
   * Return the position area code.
   *
   * @return string
   *   The position area code.
   */
  public function getAreaCode(): string {
    return $this->areaCode;
  }

  /**
   * Return true if the position is the primary position.
   *
   * @return bool
   *   True if the position is the primary position.
   */
  public function isPrimaryPosition(): bool {
    return $this->primaryPos;
  }

}
