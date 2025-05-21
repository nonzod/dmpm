<?php

namespace Drupal\myportal_autologout\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\myportal_autologout\Service\AutologoutManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * MyPortal autologout event subscriber.
 *
 * @package Drupal\myportal_autologout\EventSubscriber
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutologoutSubscriber implements EventSubscriberInterface {

  /**
   * The autologout manager service.
   *
   * @var \Drupal\myportal_autologout\Service\AutologoutManagerInterface
   */
  protected $autoLogoutManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The user storage service.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs an AutologoutSubscriber object.
   *
   * @param \Drupal\myportal_autologout\Service\AutologoutManagerInterface $autologout
   *   The autologout manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    AutologoutManagerInterface $autologout,
    TimeInterface $time,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    PrivateTempStoreFactory $temp_store_factory
  ) {
    $this->autoLogoutManager = $autologout;
    $this->time = $time;
    $this->currentUser = $current_user;
    $user_storage = $entity_type_manager->getStorage('user');
    assert($user_storage instanceof UserStorageInterface);
    $this->userStorage = $user_storage;
    $this->tempStore = $temp_store_factory->get('myportal_autologout');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 31],
    ];
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if ($this->autoLogoutManager->preventJs()) {
      return;
    }

    // Retrieve the time delay to active the autologout system.
    $delay = $this->autoLogoutManager->getUserDelay();
    $now = $this->time->getRequestTime();

    if ($this->currentUser->isAuthenticated()) {
      $user = $this->userStorage->load($this->currentUser->id());
      assert($user instanceof UserInterface);

      // If time since last login and delay is great that now,
      // not active autologout.
      if (($user->getLastLoginTime() + $delay) > $now) {
        return;
      }
    }

    // Check if anything wants to be refresh only. This URL would include the
    // javascript but will keep the login alive whilst that page is opened.
    $refresh_only = $this->autoLogoutManager->refreshOnly();
    $timeout = $this->autoLogoutManager->getUserTimeout();

    // We need a backup plan if JS is disabled.
    $autologout_last = $this->tempStore->get('autologout_last');
    if (!$refresh_only && (!empty($autologout_last) && is_numeric($autologout_last))) {
      // If time since last access is > timeout + padding, log them out.
      $diff = $now - $autologout_last;
      // Convert milliseconds to seconds (the timeout is in seconds).
      $diff = round((float) ($diff / 1000), 0, PHP_ROUND_HALF_DOWN);
      if ($diff >= $timeout) {

        // Logout user and set redirect response.
        $this->autoLogoutManager->logout();

        // Redirect.
        $event->setResponse(
          new RedirectResponse($this->autoLogoutManager->getUserRedirectUrl(), 302)
        );
      }
      else {
        $this->tempStore->set('autologout_last', $now);
      }
    }
    else {
      $this->tempStore->set('autologout_last', $now);
    }
  }

}
