<?php

declare(strict_types=1);

namespace Drupal\myaccess;

/**
 * Provides symmetric encrypt and decrypt functions.
 */
trait EncryptionTrait {

  /**
   * Encrypt a plain text.
   *
   * @param string $plain
   *   The plain text.
   * @param string $method
   *   The encryption method.
   * @param string $key
   *   The shared key.
   *
   * @return string
   *   The encrypted text.
   */
  public function encrypt(string $plain, string $method, string $key): string {
    $length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($length);
    $encrypted = openssl_encrypt($plain, $method, $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($encrypted) . '|' . base64_encode($iv);
  }

  /**
   * Decrypt an encrypted text.
   *
   * @param string $encrypted
   *   The encrypted text.
   * @param string $method
   *   The encryption method.
   * @param string $key
   *   The shared key.
   *
   * @return string
   *   The plain text.
   */
  public function decrypt(string $encrypted, string $method, string $key): string {
    if ($encrypted == '') {
      return '';
    }

    [$data, $iv] = explode('|', $encrypted);
    $iv = base64_decode($iv);

    $plain = openssl_decrypt($data, $method, $key, 0, $iv);

    return ($plain == FALSE) ? '' : $plain;
  }

}
