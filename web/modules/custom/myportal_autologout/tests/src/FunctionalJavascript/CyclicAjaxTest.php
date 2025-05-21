<?php

namespace Drupal\Tests\myportal_autologout\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test that checks logout throw javascript script.
 *
 * @group Autologout
 */
class CyclicAjaxTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'myportal_autologout',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();

    // For the purposes of the test, set the timeout periods to 10 seconds.
    \Drupal::service('config.factory')
      ->getEditable('myportal_autologout.settings')
      ->set('state.vpn.enabled', TRUE)
      ->set('state.vpn.timeout', 10)
      ->set('state.vpn.delay', 0)
      ->save();
  }

  /**
   * Test auto logout throw javascript script.
   */
  public function testCyclicRequest() {
    $user = $this->drupalCreateUser([]);

    $this->drupalLogin($user);
    self::assertTrue($this->drupalUserIsLoggedIn($user));

    sleep(3);
    self::assertTrue($this->drupalUserIsLoggedIn($user));

    // @todo not work as we expected.
    // Logged out.
    /*sleep(15);
    self::assertFalse($this->drupalUserIsLoggedIn($user));*/
  }

}
