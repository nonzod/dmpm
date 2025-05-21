<?php

declare(strict_types=1);

namespace Drupal\Tests\myaccess\Unit;

use Drupal\group\GroupMembershipLoader;
use Drupal\myaccess\Exception\LoginNotAllowedException;
use Drupal\myaccess\Exception\UserDataRetrievalException;
use Drupal\myaccess\GroupManager;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\Hmrs\CsvClient;
use Drupal\myaccess\Model\HmrsUserData;
use Drupal\myaccess\UserManager;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Log\NullLogger;

/**
 * An example test class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HmrsTest extends UnitTestCase {

  /**
   * The stubbed config factory object.
   *
   * @var \PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $configFactory;

  /**
   * The NullLogger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Virtual filesystem.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  private vfsStreamDirectory $filesystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->logger = new NullLogger();
  }

  /**
   * Test that a non-existing file throws an exception.
   *
   * @test
   */
  public function itThrowAnExceptionIfCsvDoesntExist() {
    $this->expectException(UserDataRetrievalException::class);

    $this->configFactory = $this->getConfigFactoryStub(
      [
        'myaccess.settings' => [
          'hmrs.local_csv_path' => 'file.csv',
        ],
      ]
    );

    $client = new CsvClient($this->configFactory, $this->logger);
    $client->getUserData('luca.lusso@wellnet.it');
  }

  /**
   * Test that a valid file produces a valid result in case of one record.
   *
   * @test
   */
  public function itReturnValidDataForValidFileInCaseOfOneRecord() {
    $this->configFactory = $this->getConfigFactoryStub(
      [
        'myaccess.settings' => [
          'hmrs.local_csv_path' => vfsStream::url('hmrs/hmrs.csv'),
        ],
      ]
    );

    $this->filesystem = vfsStream::setup('hmrs');

    $csv_content = <<<CSV
Global employee code;Full name;Email;External;Employee status;Position title;Position status;Company;Division;Department;Sub-Area;Sub-Area -2;Sub-Area -3;Sub-Area -4;Function;Sub-function;Legal entity;Region;Country;Sub-Region;Location;Functional Area;Area
ABCDEF12G34H567I;LUCA, LUSSO;luca.lusso@wellnet.it;-;;Business Solutions - Supporting Functions;Active;Menarini Newtech BoD;General Management;Business Solutions - Supporting Function;;;;;Governance and Compliance;Business Process/Change Management;A. Menarini Newtech S.R.L.;Europe;Italy;Italy;FI Settesanti;;Ethics Business Unit Italy
CSV;

    $csv_file = new vfsStreamFile('hmrs.csv');
    $csv_file->withContent($csv_content);
    $this->filesystem->addChild($csv_file);

    $client = new CsvClient($this->configFactory, $this->logger);
    $userData = $client->getUserData('luca.lusso@wellnet.it');

    $numberOfGroupsInAPosition = 16;
    $this->assertNotNull($userData);
    $this->assertCount($numberOfGroupsInAPosition, $userData->getRecords());
    $this->assertEquals('Business Solutions - Supporting Functions',
      $userData->getPositionTitles()[0]);
    $this->assertFalse($userData->isExternal());

    $expected = [
      'company' => 'Menarini Newtech BoD',
      'division' => 'General Management',
      'department' => 'Business Solutions - Supporting Function',
      'sub_area' => '',
      'sub_area_2' => '',
      'sub_area_3' => '',
      'sub_area_4' => '',
      'function' => 'Governance and Compliance',
      'sub_function' => 'Business Process/Change Management',
      'legal_entity' => 'A. Menarini Newtech S.R.L.',
      'region' => 'Europe',
      'country' => 'Italy',
      'sub_region' => 'Italy',
      'location' => 'FI Settesanti',
      'functional_area' => '',
      'position_area' => 'Ethics Business Unit Italy',
    ];

    $count = 0;
    foreach ($expected as $scope => $name) {
      $this->assertEquals($name, $userData->getRecords()[$count]['name']);
      $this->assertEquals($scope, $userData->getRecords()[$count]['scope']);
      $count++;
    }
  }

  /**
   * Test that a valid file produces a valid result in case of two records.
   *
   * @test
   */
  public function itReturnValidDataForValidFileInCaseOfTwoRecords() {
    $this->configFactory = $this->getConfigFactoryStub(
      [
        'myaccess.settings' => [
          'hmrs.local_csv_path' => vfsStream::url('hmrs/hmrs.csv'),
        ],
      ]
    );

    $this->filesystem = vfsStream::setup('hmrs');

    $csv_content = <<<CSV
Global employee code;Full name;Email;External;Employee status;Position title;Position status;Company;Division;Department;Sub-Area;Sub-Area -2;Sub-Area -3;Sub-Area -4;Function;Sub-function;Legal entity;Region;Country;Sub-Region;Location;Functional Area;Area
ABCDEF12G34H567I;LUCA, LUSSO;luca.lusso@wellnet.it;-;;Business Solutions - Supporting Functions;Active;Menarini Newtech BoD;General Management;Business Solutions - Supporting Function;;;;;Governance and Compliance;Business Process/Change Management;A. Menarini Newtech S.R.L.;Europe;Italy;Italy;FI Settesanti;;Ethics Business Unit Italy
ABCDEF12G34H567I;LUCA, LUSSO;luca.lusso@wellnet.it;-;Active;Technical Software Architecture Specialist;Active;Chief Executive Officer;Corporate General Management, Finance and ICT Area;Corporate Innovation & Program Office;Data Integration & Application Architecture Team;Technical Software Architecture;;;ICT Management;Develop and maintain ICT Solutions;A. Menarini Industrie Farmaceutiche Riunite S.R.L.;Europe;Italy;Italy;FI Settesanti;;Corporate Communication & Technology Services
CSV;

    $csv_file = new vfsStreamFile('hmrs.csv');
    $csv_file->withContent($csv_content);
    $this->filesystem->addChild($csv_file);

    $client = new CsvClient($this->configFactory, $this->logger);
    $userData = $client->getUserData('luca.lusso@wellnet.it');

    $number_of_groups_in_a_position = 32;
    $this->assertNotNull($userData);
    $this->assertCount($number_of_groups_in_a_position,
      $userData->getRecords());
    $this->assertEquals('Business Solutions - Supporting Functions',
      $userData->getPositionTitles()[0]);
    $this->assertEquals('Technical Software Architecture Specialist',
      $userData->getPositionTitles()[1]);
    $this->assertFalse($userData->isExternal());

    $expected_first_position = [
      'company' => 'Menarini Newtech BoD',
      'division' => 'General Management',
      'department' => 'Business Solutions - Supporting Function',
      'sub_area' => '',
      'sub_area_2' => '',
      'sub_area_3' => '',
      'sub_area_4' => '',
      'function' => 'Governance and Compliance',
      'sub_function' => 'Business Process/Change Management',
      'legal_entity' => 'A. Menarini Newtech S.R.L.',
      'region' => 'Europe',
      'country' => 'Italy',
      'sub_region' => 'Italy',
      'location' => 'FI Settesanti',
      'functional_area' => '',
      'position_area' => 'Ethics Business Unit Italy',
    ];

    $expected_second_position = [
      'company' => 'Chief Executive Officer',
      'division' => 'Corporate General Management, Finance and ICT Area',
      'department' => 'Corporate Innovation & Program Office',
      'sub_area' => 'Data Integration & Application Architecture Team',
      'sub_area_2' => 'Technical Software Architecture',
      'sub_area_3' => '',
      'sub_area_4' => '',
      'function' => 'ICT Management',
      'sub_function' => 'Develop and maintain ICT Solutions',
      'legal_entity' => 'A. Menarini Industrie Farmaceutiche Riunite S.R.L.',
      'region' => 'Europe',
      'country' => 'Italy',
      'sub_region' => 'Italy',
      'location' => 'FI Settesanti',
      'functional_area' => '',
      'position_area' => 'Corporate Communication & Technology Services',
    ];

    $count = 0;
    foreach ($expected_first_position as $scope => $name) {
      $this->assertEquals($name, $userData->getRecords()[$count]['name']);
      $this->assertEquals($scope, $userData->getRecords()[$count]['scope']);
      $count++;
    }

    $count = 16;
    foreach ($expected_second_position as $scope => $name) {
      $this->assertEquals($name, $userData->getRecords()[$count]['name']);
      $this->assertEquals($scope, $userData->getRecords()[$count]['scope']);
      $count++;
    }
  }

  /**
   * Test that ...
   *
   * @test
   */
  public function itThrowAnExceptionIfNoUserDataFound() {
    $this->expectException(LoginNotAllowedException::class);
    $this->expectExceptionMessage('Login not allowed for user luca.lusso@wellnet.it: No records found for this user');

    $external_auth = $this->createMock('Drupal\externalauth\ExternalAuthInterface');
    $request_stack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
    $current_user = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $session_manager = $this->createMock('Drupal\myaccess\SessionManagerInterface');
    $group_manager = $this->createMock('Drupal\myaccess\GroupManagerInterface');
    $user_storage = $this->createMock('Drupal\user\UserStorageInterface');
    $profile_storage = $this->createMock('Drupal\profile\ProfileStorageInterface');
    $openid_client = $this->createMock('Drupal\myaccess\OpenId\ClientInterface');
    $entity_type_manager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_type_manager
      ->expects($this->once())->method('getStorage')->with('group')
      ->willReturn($this->createMock('Drupal\Core\Entity\EntityStorageInterface'));
    $user_data = $this->createMock('Drupal\user\UserDataInterface');

    $user = $this->getMockBuilder('Drupal\user\UserInterface')->getMock();
    $user->expects($this->once())->method('block')->with();
    $user->expects($this->once())->method('save')->with();
    $user->expects($this->once())
      ->method('getEmail')
      ->with()
      ->willReturn('luca.lusso@wellnet.it');

    $user_manager = new UserManager(
      $external_auth,
      $user_storage,
      $profile_storage,
      $current_user,
      $session_manager,
      $group_manager,
      $request_stack,
      $openid_client,
      $this->logger,
      $entity_type_manager,
      $user_data
    );

    $user_manager->updateData($user, NULL);
  }

  /**
   * Test that ...
   *
   * @test
   */
  public function itCreateGroupsWithCorrectNameAndScope() {
    $positions = [
      'Business Solutions - Supporting Functions',
      'Technical Software Architecture Specialist',
    ];

    $external_auth = $this->createMock('Drupal\externalauth\ExternalAuthInterface');
    $request_stack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
    $current_user = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $session_manager = $this->createMock('Drupal\myaccess\SessionManagerInterface');
    $user_storage = $this->createMock('Drupal\user\UserStorageInterface');
    $profile_storage = $this->createMock('Drupal\profile\ProfileStorageInterface');
    $openid_client = $this->createMock('Drupal\myaccess\OpenId\ClientInterface');
    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $user_data = $this->createMock('Drupal\user\UserDataInterface');

    $user = $this->getMockBuilder('Drupal\user\UserInterface')->getMock();
    $user->expects($this->never())->method('block');
    $user->expects($this->never())->method('getEmail');
    $user->expects($this->once())->method('removeRole')->with($this->equalTo('foe'));
    $user->expects($this->once())->method('set')->with($this->equalTo('field_position_title'), $this->equalTo($positions));
    $user->expects($this->once())->method('activate')->with();
    $user->expects($this->once())->method('save')->with();

    $group_content_type_storage = $this->getMockBuilder('Drupal\group\Entity\Storage\GroupContentTypeStorageInterface')
      ->disableOriginalConstructor()->getMock();
    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()->getMock();
    $entity_type_manager->expects($this->any())->method('getStorage')->willReturn($group_content_type_storage);
    $group_content_type_storage->expects($this->any())->method('loadByProperties')->willReturn([]);

    $groupMembership = new GroupMembershipLoader($entity_type_manager, $current_user);

    $group = $this->createMock('Drupal\group\Entity\Group');
    $group_storage = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()->getMock();
    $group_storage->expects($this->any())->method('loadByProperties')->willReturn([]);
    $group_storage->expects($this->any())->method('create')->with(
      $this->equalTo(
        [
          'type' => 'flexible_group',
          'uid' => 1,
          'label' => 'Menarini Newtech BoD',
          'field_flexible_group_visibility' => 'members',
          'field_group_allowed_visibility' => ['public', 'community', 'group'],
          'field_group_allowed_join_method' => 'added',
          'field_group_scope' => GroupManagerInterface::SCOPE_COMPANY,
          'field_group_context' => 'content',
        ]
      )
    )->willReturn($group);

    $group_manager = new GroupManager(
      $group_storage,
      $groupMembership,
      $this->logger,
      $cache_backend
    );

    $user_manager = new UserManager(
      $external_auth,
      $user_storage,
      $profile_storage,
      $current_user,
      $session_manager,
      $group_manager,
      $request_stack,
      $openid_client,
      $this->logger,
      $entity_type_manager,
      $user_data
    );

    $user_data = new HmrsUserData(
      [
        [
          'name' => 'Menarini Newtech BoD',
          'scope' => GroupManagerInterface::SCOPE_COMPANY,
        ],
      ],
      $positions,
      FALSE,
      FALSE
    );

    $user_manager->updateData($user, $user_data);
  }

}
