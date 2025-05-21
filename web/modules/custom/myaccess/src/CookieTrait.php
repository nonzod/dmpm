<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expose a method to add JWT cookies to a request.
 */
trait CookieTrait {

  /**
   * Create a new request with JWT cookies attached.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to extend with cookies.
   * @param string $username
   *   The user username.
   * @param string $password
   *   The user password in plain text.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A new response object with JWT cookies attached.
   */
  public function withJwtCookies(Response $response, string $username, string $password): Response {
    $new_response = clone($response);

    $jwt_user_duration_in_minutes =
      \Drupal::config('myaccess.settings')
        ->get('jwt_user_duration_in_minutes');

    /** @var \Drupal\myaccess\OpenId\ClientInterface $client */
    $client = \Drupal::service('myaccess.oidc_client');

    $max_age = time() + (60 * $jwt_user_duration_in_minutes);
    $access_token = $client->getAccessTokenByUserCredentials(
      $username,
      $password
    );

    $cookie_com = new Cookie('jwtuser', $access_token->getToken(), $max_age,
      '/', 'menarini.com', FALSE, FALSE);
    $new_response->headers->setCookie($cookie_com);

    $cookie_net = new Cookie('jwtuser', $access_token->getToken(), $max_age,
      '/', 'menarini.net', FALSE, FALSE);
    $new_response->headers->setCookie($cookie_net);

    return $new_response;
  }

}
