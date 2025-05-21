<?php

declare(strict_types=1);

namespace Drupal\myaccess\OpenId;

use SocialConnect\Provider\AccessTokenInterface;

/**
 * Dummy access token with empty data.
 */
class NullAccessToken implements AccessTokenInterface {

  /**
   * {@inheritDoc}
   */
  public function getToken() {
    return '';
  }

  /**
   * {@inheritDoc}
   */
  public function getUserId() {
    return '';
  }

  /**
   * {@inheritDoc}
   */
  public function getExpires() {
    return 0;
  }

  /**
   * {@inheritDoc}
   */
  public function getEmail() {
    return '';
  }

}
