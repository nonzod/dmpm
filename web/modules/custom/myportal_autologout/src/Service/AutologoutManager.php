<?php

namespace Drupal\myportal_autologout\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the AutologoutManager class.
 *
 * @package Drupal\myportal_autologout\Service
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class AutologoutManager implements AutologoutManagerInterface {

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The session.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $session;

  /**
   * The config object for 'autologout.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

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
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs an AutologoutManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Session\SessionManager $sessionManager
   *   The session.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context to determine whether the route is an admin one.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store_factory
   *   The private temp storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $logger,
    SessionManager $sessionManager,
    TimeInterface $time,
    RouteMatchInterface $route_match,
    CurrentPathStack $current_path,
    AdminContext $admin_context,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    PrivateTempStoreFactory $private_temp_store_factory
  ) {
    $this->moduleHandler = $module_handler;
    $this->settings = $config_factory->get('myportal_autologout.settings');
    $this->currentUser = $current_user;
    $this->logger = $logger->get('myportal_autologout');
    $this->session = $sessionManager;
    $this->time = $time;
    $this->routeMatch = $route_match;
    $this->currentPath = $current_path;
    $this->adminContext = $admin_context;
    $user_storage = $entity_type_manager->getStorage('user');
    assert($user_storage instanceof UserStorageInterface);
    $this->userStorage = $user_storage;
    $this->requestStack = $request_stack;
    $this->tempStore = $private_temp_store_factory->get('myportal_autologout');
  }

  /**
   * {@inheritDoc}
   */
  public function createTimer() {
    return (string) $this->getRemainingTime();
  }

  /**
   * {@inheritDoc}
   */
  public function getRemainingTime() {
    $time_passed = 0;
    $autologout_last = $this->tempStore->get('autologout_last');
    if (is_numeric($autologout_last)) {
      $time_passed = $this->time->getRequestTime() - $autologout_last;
    }
    $timeout = $this->getUserTimeout();

    return (int) ($timeout - $time_passed);
  }

  /**
   * {@inheritDoc}
   */
  public function getUserTimeout(UserInterface $user = NULL) {
    if (!$user instanceof UserInterface) {
      // If $user is not provided, use the logged in user.
      $user = $this->currentUser;
    }

    if ($user->isAnonymous()) {
      // Anonymous doesn't get logged out.
      return 0;
    }

    // Return timeout.
    $timeout = $this->settings->get("state.{$this->retrieveState()}.timeout");

    return is_numeric($timeout) && $timeout > 0 ? (int) $timeout : 0;
  }

  /**
   * Retrive the current state.
   *
   * @return string
   *   The current state.
   */
  protected function retrieveState() {
    if ($this->moduleHandler->moduleExists('myaccess')) {

      // Retrieve the context from current user.
      // phpcs:ignore
      return \Drupal::service('myaccess.user_manager')
        ->isExternal() ? 'novpn' : 'vpn';
    }

    return 'vpn';
  }

  /**
   * {@inheritDoc}
   */
  public function logout() {
    if ($this->settings->get('use_watchdog')) {
      $this->logger->info(
        'Session automatically closed for %name by autologout.',
        ['%name' => $this->currentUser->getAccountName()]
      );
    }

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
  }

  /**
   * {@inheritdoc}
   */
  public function getUserRedirectUrl($uid = NULL) {
    // @todo change with the url of auth-provider.
    $redirect_url = Url::fromRoute('<front>')->toString();
    assert(is_string($redirect_url));

    return $redirect_url;
  }

  /**
   * {@inheritDoc}
   */
  public function preventJs() {

    // Check if functional is active for current state.
    if (!$this->settings->get("state.{$this->retrieveState()}.enabled")) {
      return TRUE;
    }

    // @todo use hook for custom paths?!.
    // Check by route name.
    $route_name = $this->routeMatch->getRouteName();
    $exclude_route_name = [
      'myaccess.password_form',
      'user.logout',
      'health_check.content',
      'o11y_metrics.controller_metrics',
      'hmrs_mocks.hmrs-authenticate',
      'hmrs_mocks.hmrs-endpoint',
    ];
    if ($route_name && in_array($route_name, $exclude_route_name)) {
      return TRUE;
    }

    // Don't include autologout JS checks on ajax callbacks.
    $paths = [
      'system',
      'autologout_ajax_get_time_left',
      'autologout_ajax_logout',
      'autologout_ajax_set_last',
    ];
    // getPath is used because Url::fromRoute('<current>')->toString() doesn't
    // give correct path for XHR request.
    $path_args = explode('/', $this->currentPath->getPath());

    if (in_array($path_args[1], $paths)) {
      return TRUE;
    }

    // If user is anonymous.
    if ($this->currentUser->isAnonymous()) {
      return TRUE;
    }

    // If user has no timeout set.
    if ($this->getUserTimeout() === 0) {
      _check_session_variable();

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function refreshOnly() {
    if ($this->adminContext->isAdminRoute($this->routeMatch->getRouteObject())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserDelay(UserInterface $user = NULL) {
    if (!$user instanceof UserInterface) {
      // If $user is not provided, use the logged in user.
      $user = $this->currentUser;
    }

    if ($user->isAnonymous()) {
      // Anonymous doesn't get logged out.
      return 0;
    }

    // Return timeout.
    $delay = $this->settings->get("state.{$this->retrieveState()}.delay");

    return is_numeric($delay) && $delay > 0 ? (int) $delay : 0;
  }

}
