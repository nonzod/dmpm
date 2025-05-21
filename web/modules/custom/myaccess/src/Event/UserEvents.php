<?php

declare(strict_types=1);

namespace Drupal\myaccess\Event;

/**
 * Constants for myaccess events.
 */
final class UserEvents {

  /**
   * Name of the event fired when the user login.
   *
   * @see myaccess_user_login()
   *
   * @Event
   *
   * @string
   */
  const LOGIN = 'myaccess.login';

  /**
   * Name of the event fired when the user logout.
   *
   * @todo not implement yet.
   *
   * @Event
   *
   * @string
   */
  const LOGOUT = 'myaccess.logout';

  /**
   * Name of the event fired when the user re-insert password.
   *
   * Trigger after the user compile and validate the
   * \Drupal\myaccess\Form\PasswordForm.
   *
   * @see \Drupal\myaccess\Form\PasswordForm
   *
   * @Event
   *
   * @string
   */
  const POST_REINSERT_PASSWORD = 'myaccess.post_reinsert_password';

}
