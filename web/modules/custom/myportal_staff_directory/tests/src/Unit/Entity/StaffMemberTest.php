<?php

namespace Drupal\Tests\myportal_staff_directory\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\myportal_staff_directory\Entity\StaffMember;

/**
 * @coversDefaultClass \Drupal\myportal_staff_directory\Entity\StaffMember
 * @group myportal_staff_directory
 */
class StaffMemberTest extends UnitTestCase {

  /**
   * Test for getEmployeeType method.
   *
   * @covers ::getEmployeeType
   * @dataProvider employeeTypeDataProvider
   */
  public function testGetEmployeeType($memberData, $expectedType) {
    $actualType = StaffMember::getEmployeeType($memberData);
    $this->assertEquals($expectedType, $actualType);
  }

  /**
   * Data provider for testGetEmployeeType.
   */
  public function employeeTypeDataProvider() {
    return [
      'italian_employee' => [
        [
          'Country' => 'Italy',
          'Legalentity' => 'Some Entity',
        ],
        'CORPORATE',
      ],
      'regional_employee' => [
        [
          'Country' => 'Singapore',
          'Legalentity' => 'A. Menarini Asia-Pacific Holdings Pte. Ltd.',
        ],
        'REGIONAL',
      ],
      'local_employee' => [
        [
          'Country' => 'France',
          'Legalentity' => 'Other Entity',
        ],
        'LOCAL',
      ],
    ];
  }

  /**
   * Test for getRegionalLegalentities method.
   *
   * @covers ::getRegionalLegalentities
   */
  public function testGetRegionalLegalentities() {
    $expected = [
      'A. Menarini Asia-Pacific Holdings Pte. Ltd.',
      'A. Menarini Asia-Pacific Pte. Ltd'
    ];
    
    $actual = StaffMember::getRegionalLegalentities();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test for setName method.
   *
   * @covers ::setName
   */
  public function testSetName() {
    $staffMember = $this->getMockBuilder(StaffMember::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['set'])
      ->getMock();

    $staffMember->expects($this->once())
      ->method('set')
      ->with('name', 'John Doe');

    $staffMember->setName('John', 'Doe');
  }
}