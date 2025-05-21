<?php

declare(strict_types=1);

namespace Drupal\myaccess\Exception;

/**
 * Exception thrown when the system cannot retrieve external user data.
 */
class UserDataRetrievalException extends \Exception {

  /**
   * UserDataRetrievalException constructor.
   *
   * @param string $email
   *   The email of the user that is trying to login.
   * @param string $provider
   *   The provider that is used to retrieve external user data.
   */
  public function __construct(string $email, string $provider) {
    parent::__construct(
      sprintf('No user data found for %s in provider %s', $email, $provider)
    );
  }

}
