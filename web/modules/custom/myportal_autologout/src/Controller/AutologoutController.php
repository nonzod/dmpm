<?php

namespace Drupal\myportal_autologout\Controller;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\myportal_autologout\Service\AutologoutManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for MyPortal autologout routes.
 *
 * @package Drupal\myportal_autologout\Controller
 */
class AutologoutController extends ControllerBase {

  /**
   * The autologout manager service.
   *
   * @var \Drupal\myportal_autologout\Service\AutologoutManagerInterface
   */
  protected $autoLogoutManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $autologout_manager = $container->get('myportal_autologout.manager');
    assert($autologout_manager instanceof AutologoutManagerInterface);
    $instance->autoLogoutManager = $autologout_manager;

    $datetime_time = $container->get('datetime.time');
    assert($datetime_time instanceof TimeInterface);
    $instance->time = $datetime_time;

    $request_stack = $container->get('request_stack');
    assert($request_stack instanceof RequestStack);
    $instance->requestStack = $request_stack;

    $temp_store_factory = $container->get('tempstore.private');
    assert($temp_store_factory instanceof PrivateTempStoreFactory);
    $instance->tempStore = $temp_store_factory->get('myportal_autologout');

    return $instance;
  }

  /**
   * AJAX callback that returns the time remaining for this user is logged out.
   */
  public function ajaxGetRemainingTime() {

    $req = $this->requestStack->getCurrentRequest();
    assert($req instanceof Request);
    $response = new AjaxResponse();

    $active = $req->get('uactive');
    if (isset($active) && $active === "false") {
      $response->addCommand(new ReplaceCommand('#timer', '0'));
      $response->addCommand(new SettingsCommand(['time' => '0']));

      return $response;
    }

    $time_remaining_ms = $this->autoLogoutManager->getRemainingTime() * 1000;

    // Reset the timer.
    $markup = $this->autoLogoutManager->createTimer();

    $response->addCommand(new ReplaceCommand('#timer', $markup));
    $response->addCommand(new SettingsCommand(['time' => $time_remaining_ms]));

    return $response;
  }

  /**
   * AJAX logout.
   */
  public function ajaxLogout() {
    $this->autoLogoutManager->logout();
    $response = new AjaxResponse();
    $response->setStatusCode(200);

    return $response;
  }

  /**
   * Ajax callback to reset the last access session variable.
   */
  public function ajaxSetLast() {
    $this->tempStore->set('autologout_last', $this->time->getRequestTime());

    // Reset the timer.
    $response = new AjaxResponse();
    $markup = $this->autoLogoutManager->createTimer();
    $response->addCommand(new ReplaceCommand('#timer', $markup));

    return $response;
  }

}
