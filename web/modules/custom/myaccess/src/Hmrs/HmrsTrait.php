<?php

declare(strict_types=1);

namespace Drupal\myaccess\Hmrs;

use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\Model\HmrsUserData;
use Drupal\myaccess\Model\HmrsUserRecord;

/**
 * Commons methods for Hmrs integration.
 */
trait HmrsTrait {

  /**
   * {@inheritDoc}
   */
  public function buildUserData(array $records): HmrsUserData {
    $groups = [];
    $positionTitles = [];
    $external = FALSE;
    $manager = FALSE;
    $primaryPos = FALSE;
    foreach ($records as $record) {
      assert($record instanceof HmrsUserRecord);
      $groups[] = [
        'name' => $record->getCompany(),
        'scope' => GroupManagerInterface::SCOPE_COMPANY,
      ];
      $groups[] = [
        'name' => $record->getDivision(),
        'scope' => GroupManagerInterface::SCOPE_DIVISION,
      ];
      $groups[] = [
        'name' => $record->getDepartment(),
        'scope' => GroupManagerInterface::SCOPE_DEPARTMENT,
      ];
      $groups[] = [
        'name' => $record->getSubArea(),
        'scope' => GroupManagerInterface::SCOPE_SUB_AREA,
      ];
      $groups[] = [
        'name' => $record->getSubArea2(),
        'scope' => GroupManagerInterface::SCOPE_SUB_AREA_2,
      ];
      $groups[] = [
        'name' => $record->getSubArea3(),
        'scope' => GroupManagerInterface::SCOPE_SUB_AREA_3,
      ];
      $groups[] = [
        'name' => $record->getSubArea4(),
        'scope' => GroupManagerInterface::SCOPE_SUB_AREA_4,
      ];
      $groups[] = [
        'name' => $record->getFunction(),
        'scope' => GroupManagerInterface::SCOPE_FUNCTION,
      ];
      $groups[] = [
        'name' => $record->getSubFunction(),
        'scope' => GroupManagerInterface::SCOPE_SUB_FUNCTION,
      ];
      $groups[] = [
        'name' => $record->getLegalEntity(),
        'scope' => GroupManagerInterface::SCOPE_LEGAL_ENTITY,
      ];
      $groups[] = [
        'name' => $record->getRegion(),
        'scope' => GroupManagerInterface::SCOPE_REGION,
      ];
      $groups[] = [
        'name' => $record->getCountry(),
        'scope' => GroupManagerInterface::SCOPE_COUNTRY,
      ];
      $groups[] = [
        'name' => $record->getSubRegion(),
        'scope' => GroupManagerInterface::SCOPE_SUB_REGION,
      ];
      $groups[] = [
        'name' => $record->getLocation(),
        'scope' => GroupManagerInterface::SCOPE_LOCATION,
      ];
      $groups[] = [
        'name' => $record->getFunctionalArea(),
        'scope' => GroupManagerInterface::SCOPE_FUNCTIONAL_AREA,
      ];
      $groups[] = [
        'name' => $record->getAreaCode(),
        'scope' => GroupManagerInterface::SCOPE_POSITION_AREA,
      ];

      $positionTitles[] = $record->getPositionTitle();
      $external = $record->isExternal();
      $manager = $record->isManager();
      $primaryPos = $record->isPrimaryPosition();
    }

    return new HmrsUserData($groups, $positionTitles, $external, $manager, $primaryPos);
  }

}
