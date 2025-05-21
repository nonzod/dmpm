<?php

declare(strict_types=1);

namespace Drupal\myaccess\Exception;

/**
 * Exception thrown when the user is not allowed to login.
 */
class LoginNotAllowedException extends \Exception {

  /**
   * UserDataRetrievalException constructor.
   *
   * @param string $email
   *   The email of the user that is trying to login.
   * @param string $message
   *   A detailed error message.
   */
  public function __construct(string $email, string $message) {
    parent::__construct(
      sprintf('Login not allowed for user %s: %s', $email, $message)
    );
  }

}
