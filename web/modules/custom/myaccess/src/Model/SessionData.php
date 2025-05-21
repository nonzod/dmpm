<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\myaccess\EncryptionTrait;

/**
 * Data to be stored in the user session.
 */
class SessionData {

  use EncryptionTrait;

  /**
   * The user password.
   *
   * @var string
   */
  private string $password;

  /**
   * MyAccess settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $settings;

  /**
   * TRUE if the request is external.
   *
   * @var bool|null
   */
  private ?bool $external;

  /**
   * SessionData constructor.
   *
   * @param string $password
   *   The user password.
   * @param bool|null $external
   *   TRUE if the request is external.
   */
  final private function __construct(string $password, ?bool $external = NULL) {
    $this->password = $password;
    $this->external = $external;

    $this->settings = \Drupal::config('myaccess.settings');
  }

  /**
   * Build a SessionData instance from the data in the Session.
   *
   * @param array $data
   *   The data in the Session.
   *
   * @return \Drupal\myaccess\Model\SessionData
   *   A SessionData instance.
   */
  public static function fromSession(array $data): SessionData {
    return new static(
      $data['password'] ?? '',
      $data['external'] ?? NULL
      );
  }

  /**
   * Return the user password.
   *
   * @return string
   *   The user password.
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * Return TRUE if the password is present in the session.
   *
   * @return bool
   *   TRUE if the password is present in the session.
   */
  public function hasPassword(): bool {
    return $this->password != '';
  }

  /**
   * Return TRUE if the request is external.
   *
   * @return bool|null
   *   TRUE if the request is external.
   */
  public function isExternal(): ?bool {
    return $this->external;
  }

  /**
   * Save the external value in the session.
   *
   * @param bool $external
   *   TRUE if the request is external.
   */
  public function setExternal(bool $external): void {
    $this->external = $external;
  }

  /**
   * Return the user password decrypted.
   *
   * @return string
   *   The user password.
   */
  public function getDecryptedPassword(): string {
    $method = $this->settings->get('session.password_encrypt_method');
    $key = $this->settings->get('session.password_encrypt_key');

    return $this->decrypt($this->password, $method, $key);
  }

  /**
   * Return a new instance of SessionData with password field set.
   *
   * @param string $password
   *   The new password value.
   *
   * @return \Drupal\myaccess\Model\SessionData
   *   A new instance of SessionData with password field set.
   */
  public function withPassword(string $password): SessionData {
    $method = $this->settings->get('session.password_encrypt_method');
    $key = $this->settings->get('session.password_encrypt_key');

    $new_session_data = clone $this;
    $new_session_data->password = $this->encrypt($password, $method, $key);

    return $new_session_data;
  }

  /**
   * Return a new instance of SessionData with empty password.
   *
   * @return \Drupal\myaccess\Model\SessionData
   *   A new instance of SessionData with password field set.
   */
  public function deletePassword(): SessionData {
    $new_session_data = clone $this;
    $new_session_data->password = "";
    return $new_session_data;
  }

  /**
   * Convert this object to an array.
   *
   * @return array
   *   This object as an array.
   */
  public function toArray() {
    return [
      'password' => $this->getPassword(),
      'external' => $this->isExternal(),
    ];
  }

}
