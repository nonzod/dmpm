<?php

namespace Drupal\myaccess\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\UncacheableRedirectTrait;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class AuthenticationSubscriber.
 *
 * This event subscriber trigger the OpenID Connect authentication flow when an
 * anonymous user try to load a protected resource.
 */
class AuthenticationSubscriber extends HttpExceptionSubscriberBase {

  use UncacheableRedirectTrait;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  protected $client;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * AuthenticationSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\myaccess\OpenId\ClientInterface $client
   *   The OpenId client service.
   */
  public function __construct(
    AccountInterface $current_user,
    ClientInterface $client
  ) {
    $this->currentUser = $current_user;
    $this->client = $client;
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function on403(GetResponseEvent $event): void {
    if ($this->currentUser->isAnonymous() && $event->isMasterRequest()) {

      $request = $event->getRequest();
      if ($request->query->has('destination_uri')) {
        $destination_uri = $request->query->get('destination_uri');
        // store the destination uri in the session (keycloak no longer retains this data)
        $_SESSION['myaccess_destination_uri'] = $destination_uri;
        // Create the authentication url with redirect destination.
        $url = $this->client->makeAuthUrl($destination_uri);
      }
      else {
        // Create the authentication url without redirect destination.
        $url = $this->client->makeAuthUrl(NULL);
      }

      // Create the un-cacheable redirect response.
      $response = $this->buildUncacheableRedirect($url);

      // Set the response to the event.
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

}
