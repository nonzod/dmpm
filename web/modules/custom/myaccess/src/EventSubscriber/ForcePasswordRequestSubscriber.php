<?php

declare(strict_types=1);

namespace Drupal\myaccess\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Drupal\myaccess\SessionManagerInterface;
use Drupal\myaccess\UncacheableRedirectTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ForcePasswordRequestSubscriber.
 *
 * Redirect users to the password insert form if password is missing in the
 * session.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ForcePasswordRequestSubscriber implements EventSubscriberInterface {

  use UncacheableRedirectTrait;
  use RedirectDestinationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * The Session manager service.
   *
   * @var \Drupal\myaccess\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * Langauge manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * Language negotiation settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $languageNegotiationConfig;

  /**
   * The Masquerade service or null if the Masquerade module isn't installed.
   *
   * @var \Drupal\masquerade\Masquerade|null
   */
  private ?Masquerade $masquerade;

  /**
   * ForcePasswordRequestSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\myaccess\SessionManagerInterface $session_manager
   *   The Session manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\masquerade\Masquerade|null $masquerade
   *   The Masquerade service or null if the Masquerade module isn't installed.
   */
  public function __construct(
    AccountInterface $current_user,
    SessionManagerInterface $session_manager,
    LanguageManagerInterface $language_manager,
    ConfigFactoryInterface $config,
    ?Masquerade $masquerade = NULL
  ) {
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
    $this->languageManager = $language_manager;
    $this->languageNegotiationConfig = $config->get('language.negotiation');
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // Run this subscriber as earlier as possibile.
    $events[KernelEvents::REQUEST][] = [
      'forcePasswordRequest',
      29,
    ];

    return $events;
  }

  /**
   * Check if a logged in user has filled the password form.
   *
   * Force the user to fill the password form.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function forcePasswordRequest(GetResponseEvent $event): void {
    if($this->currentUser->isAuthenticated()) {
      return;
    }
    if (($this->masquerade != NULL) && $this->masquerade->isMasquerading()) {
      return;
    }

    if ($this->canBypass($this->currentUser)) {
      return;
    }

    if ($this->isPasswordInSession($this->sessionManager)) {

      // Custom redirect if found 'destination_uri' params query.
      if ($event->getRequest()->query->has('destination_uri')) {
        $destination_uri = $event->getRequest()->query->get('destination_uri');
        $url = Url::fromUserInput($destination_uri)->toString();
        assert(is_string($url));

        $response = $this->buildUncacheableRedirect($url);

        // Set the response to the event.
        $event->setResponse($response);
      }

      return;
    }

    if ($this->isAllowedPath($event)) {
      return;
    }

    // At this point we need to redirect the user to the password form.
    // The password form route (myaccess.password_form) is accessibile only for
    // authenticated users, so Drupal will redirect anonymous users to the
    // external authentication system.
    // In case of ajax requests we cannot perform such redirect otherwise the
    // browser will throw an exception. We must return a valid (200) response
    // in any case. So here we're returning an AjaxResponse that contains a
    // RedirectCommand that will force the browser to trigger a redirect to
    // the front page. The front page route is accessible only for authenticated
    // user and this will trigger the standard login process.
    if ($event->getRequest()->isXmlHttpRequest()) {
      $this->redirectRequest($event);
    }
    else {
      $this->updateRequest($event);
    }
  }

  /**
   * Check if the user has 'bypass password request' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   *
   * @return bool
   *   TRUE if the user has 'bypass password request' permission.
   */
  private function canBypass(AccountInterface $current_user): bool {
    return $current_user->hasPermission('bypass password request');
  }

  /**
   * Check if there is a password in the session.
   *
   * @param \Drupal\myaccess\SessionManagerInterface $session_manager
   *   The Session service.
   *
   * @return bool
   *   TRUE if there is a password in the session.
   */
  private function isPasswordInSession(SessionManagerInterface $session_manager): bool {
    $session_data = $session_manager->getAll();

    return $session_data->getPassword() != '';
  }

  /**
   * Check if the current request is for a route that can be seen by anonymous.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @return bool
   *   TRUE if the current request is for a route that can be seen by anonymous.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  private function isAllowedPath(GetResponseEvent $event): bool {

    // Check by route name.
    $route_match = RouteMatch::createFromRequest($event->getRequest());
    if ($route_match instanceof RouteMatchInterface && in_array($route_match->getRouteName(), [
        'myaccess.oidc_login_force',
        'myaccess.password_form',
        'myaccess.login',
        'user.login',
        'user.logout',
        'myaccess.blocked',
        'myaccess.login_with_access_token',
        'health_check.content',
        'o11y_metrics.controller_metrics',
        'hmrs_mocks.hmrs-authenticate',
        'hmrs_mocks.hmrs-endpoint',
        'myaccess_mocks.login',
        'system.403',
        'system.404',
      ])) {
      return TRUE;
    }

    // Check by path.
    $path = $event->getRequest()->getPathInfo();
    $language_prefix = $this->getLanguagePrefix();
    if (substr($path, 0, strlen($language_prefix)) == $language_prefix) {
      $path = "/" . substr($path, strlen($language_prefix));
    }

    return $path == '/password' ||
      $path == '/oidc/login' ||
      $path == '/oidc/login-with-access-token' ||
      $path == '/admin/login' ||
      $path == '/blocked' ||
      $path == '/health' ||
      $path == '/metrics' ||
      $path == '/hmrs/authenticate' ||
      $path == '/hmrs/hmrs-endpoint' ||
      $path == '/mocks/login';
  }

  /**
   * Retrieve the language prefix.
   *
   * @return string
   *   The language prefix.
   */
  private function getLanguagePrefix() {
    $prefixes = $this->languageNegotiationConfig->get('url.prefixes');
    $language = $this->languageManager->getCurrentLanguage()->getId();

    return isset($prefixes[$language]) ? "/" . $prefixes[$language] : '/';
  }

  /**
   * Change the response to be a redirect one.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  private function updateRequest(GetResponseEvent $event): void {
    $url = Url::fromRoute('myaccess.oidc_login_force')
      ->setOption('query', [
        'destination_uri' => $this->getRedirectDestination()->get(),
      ])
      ->toString();
    assert(is_string($url));

    $response = $this->buildUncacheableRedirect($url);

    // Set the response to the event.
    $event->setResponse($response);
  }

  /**
   * Return an ajax response that will trigger a redirect to the front page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  private function redirectRequest(GetResponseEvent $event): void {
    $url = Url::fromRoute('<front>')->toString();
    assert(is_string($url));

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));

    // Set the response to the event.
    $event->setResponse($response);
  }

}
