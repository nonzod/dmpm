<?php

namespace Drupal\myaccess\Controller;

use \Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\HttpFoundation\JsonResponse;


class OIDCTokenController extends ControllerBase {

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $user_manager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  protected $client;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The current user is using the "masquerade" feature
   *
   * @var bool
   */
  protected $is_masquerading = FALSE;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $user_manager = $container->get('myaccess.user_manager');
    $instance->user_manager = $user_manager;
    $instance->current_user = $container->get('current_user');

    $client = $container->get('myaccess.oidc_client');
    $instance->client = $client;

    $instance->userData = $container->get('user.data');

    $instance->config = \Drupal::config('myaccess.oidc_token_refresh_settings');

    if(\Drupal::hasService('masquerade')){
      /** @var \Drupal\masquerade\Masquerade $masquerade_serv */
      $masquerade_serv = \Drupal::service('masquerade');
      $instance->is_masquerading = $masquerade_serv->isMasquerading();
    }

    return $instance;
  }
  /**
   * Only for testing purposes
   * (path with /uid parameter can be accessed only by admin)
   *
   * @param $uid
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function tokenInfo($uid) {
    $uid = $this->checkUid($uid);
    if(!$uid) {
      return new JsonResponse(['error' => 'Access Denied']);
    }
    $saved_token_data = $this->user_manager->getSavedTokenInfo($uid);
    $resp = new JsonResponse($saved_token_data);
    return $resp;
  }

  /**
   * @param $uid
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function tokenRefresh($uid) {
    $uid = $this->checkUid($uid);
    if(!$uid) {
      return new JsonResponse(['error' => 'Access Denied']);
    } elseif($this->is_masquerading) {
      return new JsonResponse([
        'status' => 'ok',
        'message' => 'masquerading',
        'is_oidc' => FALSE,
        'refresh_expires_in' => 999999
      ]);
    }
    $refresh_mode = $this->config->get('refresh_mode');
    $is_oidc = !empty($this->userData->get('myaccess', $this->current_user->id(), 'is_oidc_user'));
    $resp_data = [
      'status' => 'ok',
      'message' => '-',
      'is_oidc' => $is_oidc,
      'refresh_expires_in' => $this->config->get('refresh_time_interval') * 60
    ];
    if($is_oidc && $refresh_mode == 'full') {
      $saved_token_data = $this->user_manager->getSavedTokenInfo($uid);
      if(time() > $saved_token_data['refresh_expires'] ) {
        $resp_data['status'] = 'ko';
        $resp_data['message'] = 'expired_token';
        $resp_data['refresh_expires_in'] = 0;
      } elseif($saved_token_data['refresh_expires'] - time() < 60 ) {
        $new_token_data = $this->client->refreshTokenBySavedRefreshToken($saved_token_data);
        if($new_token_data && !empty($new_token_data['refresh_token'])) {
          $refresh_expires_in = $new_token_data['refresh_expires_in'];
          $new_token_data['expires'] = time() + $new_token_data['expires_in'];
          $new_token_data['refresh_expires'] = time() + $new_token_data['refresh_expires_in'];
          $this->user_manager->saveTokenInfo($uid, $new_token_data);
          $resp_data['status'] = 'ok';
          $resp_data['message'] = 'refreshed_token';
          $resp_data['refresh_expires_in'] = $refresh_expires_in - 30;
        } else {
          $resp_data['status'] = 'ko';
          $resp_data['message'] = 'error_refreshing_token';
        }
      } else {
        $resp_data['status'] = 'ok';
        $resp_data['message'] = 'still_valid_token';
        $resp_data['refresh_expires_in'] = max(((int)$saved_token_data['refresh_expires'] - time() - 30), 10);
      }
    }
    $resp = new JsonResponse($resp_data);
    return $resp;
  }

  /**
   * Poll/ping mode (without logging out the user nor refreshing the token)
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function pollRefresh() {
    $data = ['time' => time()];
    return new JsonResponse($data);
  }

  protected function checkUid($uid): int {
    if(empty($uid) || !is_numeric($uid)) {
      $uid = $this->current_user->id();
    } else {
      if (!$this->current_user->hasPermission('administer site configuration')) {
        $uid = 0;
      } else {
        $uid = (int) $uid;
      }
    }
    return $uid;
  }
}
