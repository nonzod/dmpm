<?php

declare(strict_types=1);

namespace Drupal\myaccess\OpenId;

use SocialConnect\Provider\Session\SessionInterface as SocialConnectSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface as SymfonySessionInterface;

/**
 * Map data between SocialConnect and Symfony SessionInterface.
 */
class Session implements SocialConnectSessionInterface {

  /**
   * The Session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  private $session;

  /**
   * Session constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The Session service.
   */
  public function __construct(SymfonySessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritDoc}
   */
  public function get($key) {
    return $this->session->get($key);
  }

  /**
   * {@inheritDoc}
   */
  public function set($key, $value): void {
    $this->session->set($key, $value);
  }

  /**
   * {@inheritDoc}
   */
  public function delete($key): void {
    $this->session->remove($key);
  }

}
