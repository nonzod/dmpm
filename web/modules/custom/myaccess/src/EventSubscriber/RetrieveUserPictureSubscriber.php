<?php

declare(strict_types=1);

namespace Drupal\myaccess\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\myaccess\Event\UserEvents;
use Drupal\myaccess\Event\UserPostReinsertPasswordEvent;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\OpenId\NullAccessToken;
use Drupal\myaccess\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Retrieve the user picture from Google account.
 *
 * @package Drupal\myaccess\EventSubscriber
 */
class RetrieveUserPictureSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  protected ClientInterface $client;

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected UserManagerInterface $userManager;

  /**
   * ForcePasswordRequestSubscriber constructor.
   *
   * @param \Drupal\myaccess\OpenId\ClientInterface $client
   *   The OpenId client service.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The User Manager service.
   */
  public function __construct(ClientInterface $client, UserManagerInterface $user_manager) {
    $this->client = $client;
    $this->userManager = $user_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[UserEvents::POST_REINSERT_PASSWORD][] = ['retrieveUserPicture'];

    return $events;
  }

  /**
   * Update user data with the picture from Google.
   *
   * @param \Drupal\myaccess\Event\UserPostReinsertPasswordEvent $event
   *   The object event.
   */
  public function retrieveUserPicture(UserPostReinsertPasswordEvent $event): void {
    $access_token = $event->getAccessToken();
    if (empty($access_token) || $access_token instanceof NullAccessToken) {
      // Without access token can't retrieve the user picture.
      return;
    }

    try {
      $payload = $this->client->getOriginalIdTokenFromGoogle($access_token);

      if (isset($payload['picture'])) {
        $this->userManager->updateUserPicture($payload['picture']);
      }
    }
    catch (\Exception $exception) {
      $this->getLogger('myaccess')
        ->error('RetrieveUserPictureSubscriber throw exception with user "@user": @message.', [
          '@user' => $event->getUser()->getEmail(),
          '@message' => $exception->getMessage(),
        ]);
    }
  }

}
