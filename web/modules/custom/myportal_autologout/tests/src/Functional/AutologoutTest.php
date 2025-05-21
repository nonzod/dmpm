<?php

namespace Drupal\Tests\myportal_autologout\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines the AutologoutTest class.
 *
 * @package Drupal\Tests\myportal_autologout\Functional
 * @group myportal_autologout
 */
class AutologoutTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'myportal_autologout',
  ];

  /**
   * User with admin rights.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $privilegedUser;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Performs any pre-requisite tasks that need to happen.
   */
  public function setUp(): void {
    parent::setUp();

    // For the purposes of the test, set the timeout periods to 5 seconds.
    $this->configFactory = $this->container->get('config.factory');
    $this->configFactory->getEditable('myportal_autologout.settings')
      ->set('state.vpn.timeout', 5)
      ->set('state.vpn.delay', 0)
      ->set('state.vpn.enabled', TRUE)
      ->save();

    // Create and log in user.
    $this->privilegedUser = $this->drupalCreateUser([
      'access content',
      'administer site configuration',
      'access site reports',
      'access administration pages',
      'bypass node access',
      'administer content types',
      'administer nodes',
      'administer myportal_autologout configuration',
      'access site reports',
      'view the administration theme',
    ]);
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Tests a user is logged out after the timeout period.
   */
  public function testAutologoutDefaultTimeout() {

    // Check that the user can access the page after login.
    $this->drupalGet($this->buildUrl(''));
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Wait for timeout period to elapse.
    sleep(10);

    // Check we are now logged out.
    $this->drupalGet($this->buildUrl(''));
    $this->assertSession()->statusCodeEquals(200);
    self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
  }

  /**
   * Tests a user is logged out after the timeout and delay period.
   */
  public function testAutologoutDefaultTimeoutWithDelay() {
    $autologout_settings = $this->configFactory->getEditable('myportal_autologout.settings');
    $autologout_settings
      ->set('state.vpn.timeout', 3)
      ->set('state.vpn.delay', 10)
      ->save();

    // Wait for timeout period.
    sleep(5);

    // Check that the user can access the page after timeout period.
    $this->drupalGet($this->buildUrl(''));
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Wait for delay period to elapse.
    sleep(10);

    // Check we are now logged out.
    $this->drupalGet($this->buildUrl(''));
    $this->assertSession()->statusCodeEquals(200);
    self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
  }

  /**
   * Tests a user is not logged out within the default timeout period.
   */
  public function testAutologoutNoLogoutInsideTimeout() {
    // Check that the user can access the page after login.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Wait within the timeout period.
    sleep(2);

    // Check we are still logged in.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));
  }

  /**
   * Tests the user not being logged out if autologout is disabled.
   */
  public function testAutologoutDisabled() {
    $autologout_settings = $this->configFactory->getEditable('myportal_autologout.settings');

    // Disable autologout.
    $autologout_settings
      ->set('state.vpn.enabled', FALSE)
      ->save();

    // Set time out for 5 seconds.
    $autologout_settings
      ->set('state.vpn.timeout', 5)
      ->save();

    // Wait for 10 seconds for timeout.
    sleep(10);

    // Check if we are still logged in.
    $this->drupalGet($this->buildUrl(''));
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));
  }

  /**
   * Tests that the settings update is reflected on cached front pages.
   */
  public function testAutologoutSettingsCache() {
    // Visit the user profile page to cache it and test JS timeout variable.
    $this->drupalGet('');
    $jsSettings = $this->getDrupalSettings();
    $this->assertEquals(5000, $jsSettings['myportal_autologout']['timeout']);

    // Update the timeout variable and reload the user profile page.
    $autologout_settings = $this->configFactory->getEditable('myportal_autologout.settings');
    $autologout_settings
      ->set('state.vpn.timeout', 15)
      ->save();
    $this->drupalGet('');

    // Test that the JS timeout variable is updated.
    $jsSettings = $this->getDrupalSettings();
    $this->assertEquals(15000, $jsSettings['myportal_autologout']['timeout']);
  }

  /**
   * Tests the user not being logged out if navigate in admin pages.
   */
  public function testAutologoutAdminPages() {
    // Verify admin should not be logged out.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals('200');

    // Wait until timeout.
    sleep(10);

    // Verify admin should not be logged out.
    $this->drupalGet('admin/reports/status');
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));
  }

}
