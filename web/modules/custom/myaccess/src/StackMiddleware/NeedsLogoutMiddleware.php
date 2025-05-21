<?php

declare(strict_types=1);

namespace Drupal\myaccess\StackMiddleware;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\myaccess\SessionManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Defines the NeedsLogoutMiddleware class.
 *
 * @package Drupal\myaccess\StackMiddleware
 */
class NeedsLogoutMiddleware implements HttpKernelInterface {

  use StringTranslationTrait;

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected HttpKernelInterface $httpKernel;

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
   * Constructs a IsExternalMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
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
    HttpKernelInterface $http_kernel,
    SessionManagerInterface $sessionManager,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $logger,
    SessionManager $session_manager,
    MessengerInterface $messenger
  ) {
    $this->httpKernel = $http_kernel;
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
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if (!($type === self::MASTER_REQUEST && PHP_SAPI !== 'cli')) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $session_data = $this->sessionManager->getAll();
    $previous_external = $session_data->isExternal();
    // See \Drupal\myaccess\StackMiddleware\IsExternalMiddleware::handle().
    $current_external = $request->attributes->get(IsExternalMiddleware::KEY, TRUE);

    // First request, save the value of `external` into the session and
    // continue.
    if ($previous_external === NULL) {
      $session_data->setExternal($current_external);
      $this->sessionManager->save($session_data);

      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Subsequent requests, if previous and current values of `external` are the
    // same continue.
    if ($previous_external == $current_external) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // If previous and current values of `external` are different we've
    // detected a network change. Logout the user and issue a redirect to the
    // front page.

    // TODO: the $this->currentUser not contains the correct user.

    // Log.
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

    return new RedirectResponse('/');
  }

}
