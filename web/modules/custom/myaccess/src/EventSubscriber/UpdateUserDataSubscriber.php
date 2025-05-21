<?php

declare(strict_types=1);

namespace Drupal\myaccess\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\myaccess\Event\UserEvents;
use Drupal\myaccess\Event\UserLoginEvent;
use Drupal\myaccess\Exception\LoginNotAllowedException;
use Drupal\myaccess\Exception\UpdateUserGroupsException;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\Hmrs\ClientInterface;
use Drupal\myaccess\Memoize;
use Drupal\myaccess\SessionManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for update user data from HMRS.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateUserDataSubscriber implements EventSubscriberInterface {

  use Memoize;

  /**
   * The Session manager service.
   *
   * @var \Drupal\myaccess\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The Hmrs client service.
   *
   * @var \Drupal\myaccess\Hmrs\ClientInterface
   */
  protected $client;

  /**
   * The User manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * The Group manager service.
   *
   * @var \Drupal\myaccess\GroupManagerInterface
   */
  protected GroupManagerInterface $groupManager;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * ForcePasswordRequestSubscriber constructor.
   *
   * @param \Drupal\myaccess\SessionManagerInterface $session_manager
   *   The Session manager service.
   * @param \Drupal\myaccess\Hmrs\ClientInterface $client
   *   The Hmrs client service.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The User manager service.
   * @param \Drupal\myaccess\GroupManagerInterface $group_manager
   *   The Group manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    SessionManagerInterface $session_manager,
    ClientInterface $client,
    UserManagerInterface $user_manager,
    GroupManagerInterface $group_manager,
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory
  ) {
    $this->sessionManager = $session_manager;
    $this->client = $client;
    $this->userManager = $user_manager;
    $this->groupManager = $group_manager;
    $this->logger = $logger;
    $this->config = $config_factory->get('myaccess.settings');
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[UserEvents::LOGIN][] = ['updateUserData'];

    return $events;
  }

  /**
   * Update user applications with data from BusinessAccess.
   *
   * This method may block the user account if no data is available
   * in the HMRS system.
   *
   * @param \Drupal\myaccess\Event\UserLoginEvent $event
   *   A user login event.
   */
  public function updateUserData(UserLoginEvent $event): void {
    $user = $event->getUser();

    // Some local users (like admin) are not available in the HMRS system. We
    // have to exit now to prevent to block them.
    if ($user->hasPermission('bypass hmrs check')) {
      $this->logger->info('Skip HMRS check for current user (@user).', ['@user' => $user->getAccountName()]);

      return;
    }

    try {
      $cached_client = $this->memoize(
        [$this, 'retrieveAndUpdateUserData'],
        function (UserInterface $user): string {
          return sprintf('myaccess:user_data:%s', $user->getEmail() ?? '');
        },
        ['user_data'],
        $this->config->get('event_subscriber.time_within_which_not_update') ?? 0
      );

      $cached_client($user);
    }
    catch (LoginNotAllowedException $e) {
      $this->logger->warning('User "@user" cannot login: @message.', [
        '@user' => $user->getAccountName(),
        '@message' => $e->getMessage(),
      ]);

      $event->setLoginAllowed(FALSE);
    }
    catch (UpdateUserGroupsException $e) {
      // @todo add exception to $event.
      $this->logger->error('User "@user" groups cannot be updated: @message.', [
        '@user' => $user->getAccountName(),
        '@message' => $e->getMessage(),
      ]);
    }
    catch (\Exception $e) {
      // @todo add exception to $event.
      $this->logger->error('UpdateUserDataSubscriber throw exception with user "@user": @message.', [
        '@user' => $user->getAccountName(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Retrive user data from hmrs and use it to update the user profile.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user profile to update.
   *
   * @throws \Drupal\myaccess\Exception\UserDataRetrievalException
   * @throws \Drupal\myaccess\Exception\UpdateUserGroupsException
   * @throws \Drupal\myaccess\Exception\LoginNotAllowedException
   */
  protected function retrieveAndUpdateUserData(UserInterface $user) {
    $email = $user->getEmail();

    // Cannot proceed without an email address.
    if (!$email) {
      return;
    }

    $this->logger->info('Retrieve data for user "@mail" with "@client" client.', [
      '@mail' => $email,
      '@client' => get_class($this->client),
    ]);

    // Extract the user data from the HMRS system.
    $userData = $this->client->getUserData($email);

    // Update the user profile.
    $this->userManager->updateData($user, $userData);

    $this->logger->info('Updated userData for current user (@user).', ['@user' => $user->getAccountName()]);
  }

}
