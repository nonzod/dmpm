<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\myaccess\Model\SessionData;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Manage integration with the user session.
 */
class SessionManager implements SessionManagerInterface {

  const NAMESPACE = 'myaccess';

  /**
   * The Session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  private SessionInterface $session;

  /**
   * SessionManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The Session service.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritDoc}
   */
  public function getAll(): SessionData {
    $data = $this->session->get(self::NAMESPACE, []);

    return SessionData::fromSession($data);
  }

  /**
   * {@inheritDoc}
   */
  public function save(SessionData $data): void {
    $this->session->set(self::NAMESPACE, $data->toArray());
  }

}
