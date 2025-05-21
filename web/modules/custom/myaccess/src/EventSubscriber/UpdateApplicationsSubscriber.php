<?php

declare(strict_types=1);

namespace Drupal\myaccess\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\myaccess\ApplicationsManagerInterface;
use Drupal\myaccess\Event\UserEvents;
use Drupal\myaccess\Event\UserLoginEvent;
use Drupal\myaccess\FavoriteManagerInterface;
use Drupal\myaccess\Memoize;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\SessionManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for update user applications.
 */
class UpdateApplicationsSubscriber implements EventSubscriberInterface {

  use Memoize;

  /**
   * The Session manager service.
   *
   * @var \Drupal\myaccess\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  private $client;

  /**
   * The Applications manager service.
   *
   * @var \Drupal\myaccess\ApplicationsManagerInterface
   */
  private $applicationsManager;

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  private $userManager;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The Favorite Manager Service.
   *
   * @var \Drupal\myaccess\FavoriteManagerInterface
   */
  private $favoriteManager;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * ForcePasswordRequestSubscriber constructor.
   *
   * @param \Drupal\myaccess\SessionManagerInterface $session_manager
   *   The Session manager service.
   * @param \Drupal\myaccess\OpenId\ClientInterface $client
   *   The OpenId client service.
   * @param \Drupal\myaccess\ApplicationsManagerInterface $applications_manager
   *   The Applications manager service.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The User Manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\myaccess\FavoriteManagerInterface $favorite_manager
   *   The FavoriteManager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    SessionManagerInterface $session_manager,
    ClientInterface $client,
    ApplicationsManagerInterface $applications_manager,
    UserManagerInterface $user_manager,
    LoggerInterface $logger,
    FavoriteManagerInterface $favorite_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->sessionManager = $session_manager;
    $this->client = $client;
    $this->applicationsManager = $applications_manager;
    $this->userManager = $user_manager;
    $this->logger = $logger;
    $this->favoriteManager = $favorite_manager;
    $this->config = $config_factory->get('myaccess.settings');
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[UserEvents::LOGIN][] = ['updateApplications'];

    return $events;
  }

  /**
   * Update user applications with data from BusinessAccess.
   *
   * @param \Drupal\myaccess\Event\UserLoginEvent $event
   *   A user login event.
   */
  public function updateApplications(UserLoginEvent $event): void {
    $user = $event->getUser();

    try {
      $cached_client = $this->memoize(
        [$this, 'retrieveAndUpdateApplicationsOfUser'],
        function (UserInterface $user): string {
          return sprintf('myaccess:applications:%s:%s', $user->getAccountName(), $this->userManager->isExternal() ? 'ext' : 'int');
        },
        ['applications_data'],
        $this->config->get('event_subscriber.time_within_which_not_update') ?? 0
      );

      $cached_client($user);
    }
    catch (\Exception $e) {
      // @todo add exception to $event.
      $this->logger->error('UpdateApplicationsSubscriber throw exception with user "@user": @message.', [
        '@user' => $user->getAccountName(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Retrieve and update the applications of user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user profile.
   *
   * @throws \Exception
   */
  protected function retrieveAndUpdateApplicationsOfUser(UserInterface $user) {
    $user_name = $user->getAccountName();

    // Retrieve the external applications.
    $external_applications = $this->client->getExternalApplications($user_name, $this->userManager->isExternal());

    // Save or update applications.
    $applications = $this->applicationsManager->saveOrUpdate($external_applications);

    // Attach applications to the user.
    $this->userManager->attachApplications($user, $applications);

    // Remove application denied in favorite.
    $this->favoriteManager->removeZombieApplications($user, $applications);

    $this->logger->info('Updated applications for current user (@user).', ['@user' => $user_name]);
  }

}
