<?php

namespace Drupal\myaccess\CacheContext;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\myaccess\UserManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the IsExternal CacheContext, for "per navigation type" caching.
 *
 * The navigation user can be in VPN or not.
 * Cache context ID: 'myaccess_is_external'.
 *
 * @package Drupal\myaccess\CacheContext
 */
class IsExternalCacheContext extends RequestStackCacheContextBase {

  /**
   * The user manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * Construct new IsExternalCacheContext instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The user manager service.
   */
  public function __construct(RequestStack $request_stack, UserManagerInterface $user_manager) {
    parent::__construct($request_stack);
    $this->userManager = $user_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is External request from VPN cache context');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->userManager->isExternal() ? 'external' : 'internal';
  }

}
