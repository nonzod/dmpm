<?php

namespace Drupal\myaccess\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\myaccess\SessionManagerInterface;
use Drupal\myaccess\StackMiddleware\IsExternalMiddleware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines the NeedsLogoutSubscriber class.
 *
 * @package Drupal\myaccess\EventSubscriber
 */
class NeedsLogoutSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The Session manager service.
   *
   * @var \Drupal\myaccess\SessionManagerInterface
   */
  protected SessionManagerInterface $sessionManager;

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The session.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $session;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Construct new NeedsLogoutSubscriber instance.
   *
   * @param \Drupal\myaccess\SessionManagerInterface $sessionManager
   *   The Session manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Session\SessionManager $session_manager
   *   The session.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    SessionManagerInterface $sessionManager,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $logger,
    SessionManager $session_manager,
    MessengerInterface $messenger
  ) {
    $this->sessionManager = $sessionManager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->logger = $logger->get('myaccess');
    $this->session = $session_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The priority must be lower than the AuthenticationSubscriber
    // for accessing the current user.
    $events[KernelEvents::REQUEST][] = ['onKernelRequestNeedsLogout', 290];
    return $events;
  }

  /**
   * Check needs logout for change network on request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onKernelRequestNeedsLogout(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $session_data = $this->sessionManager->getAll();
    $previous_external = $session_data->isExternal();
    // See \Drupal\myaccess\StackMiddleware\IsExternalMiddleware::handle().
    $current_external = $request->attributes->get(IsExternalMiddleware::KEY, TRUE);

    // Save the value of `external` into the session and continue.
    if ($previous_external === NULL) {
      $session_data->setExternal($current_external);
      $this->sessionManager->save($session_data);

      return;
    }

    // If previous and current values of `external` are the
    // same continue.
    if ($previous_external == $current_external) {
      return;
    }

    // If previous and current values of `external` are different we've
    // detected a network change. Logout the user and issue a redirect to the
    // front page.
    $this->logger->info(
      'Session automatically closed for %name because detected a network change: %from to %to.', [
        '%name' => $this->currentUser->getAccountName(),
        '%from' => $current_external ? 'external' : 'internal',
        '%to' => $previous_external ? 'external' : 'internal',
      ]
    );

    // @see user_logout().
    // Destroy the current session.
    $this->moduleHandler->invokeAll('user_logout', [$this->currentUser]);

    // Destroy the current session, and reset $user to the anonymous user.
    // Note: In Symfony the session is intended to be destroyed with
    // Session::invalidate(). Regrettably this method is currently broken and
    // may lead to the creation of spurious session records in the database.
    // @see https://github.com/symfony/symfony/issues/12375
    $this->session->destroy();
    $this->currentUser->setAccount(new AnonymousUserSession());

    // Show message.
    $this->messenger->addWarning(
      $this->t("You have just been logged out due to a network state change. Please log in again.")
    );

    $event->setResponse(new RedirectResponse('/', 302));
  }

}
