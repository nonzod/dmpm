<?php

declare(strict_types=1);

namespace Drupal\myaccess\OpenId;

use Drupal\myaccess\Model\ExternalUser;
use Drupal\myaccess\Model\MyAccessData;
use SocialConnect\Provider\AccessTokenInterface;

/**
 * Defines the Interface for OpenId Connect clients.
 *
 * @package Drupal\myaccess\OpenId
 */
interface ClientInterface {

  /**
   * Generate the URL used to redirect the browser to the authentication page.
   *
   * @return string
   *   The generated URL.
   */
  public function makeAuthUrl(): string;

  /**
   * Generate the URL used to logout the user.
   *
   * @return string
   *   The generated URL.
   */
  public function makeLogoutUrl(): string;

  /**
   * Retrieve the Access Token using params from the query string.
   *
   * @param array $query
   *   The query string as array.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface
   *   The retrieved Access Token.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getAccessTokenByRequestParameters(array $query): AccessTokenInterface;

  /**
   * Retrieve the Access Token by User credentials.
   *
   * @param string $username
   *   The user username.
   * @param string $password
   *   The user password.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface
   *   The retrieved Access Token or NULL if credentials are wrong.
   */
  public function getAccessTokenByUserCredentials(string $username, string $password): AccessTokenInterface;

  /**
   * Retrieve the token that Google returns to Keycloak after authentication.
   *
   * @param \SocialConnect\Provider\AccessTokenInterface $accessToken
   *   The Keycloak Access Token.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface
   *   The token that Google returns to Keycloak after authentication.
   */
  public function getOriginalAccessTokenFromGoogle(AccessTokenInterface $accessToken): AccessTokenInterface;

  /**
   * Retrieve the id_token that Google returns to Keycloak after authentication.
   *
   * @param \SocialConnect\Provider\AccessTokenInterface $accessToken
   *   The Keycloak Access Token.
   *
   * @return array
   *   The id_token that Google returns to Keycloak after authentication.
   */
  public function getOriginalIdTokenFromGoogle(AccessTokenInterface $accessToken): array;

  /**
   * Retrieve the user from the Access Token received.
   *
   * @param \SocialConnect\Provider\AccessTokenInterface $accessToken
   *   The Access Token.
   *
   * @return \Drupal\myaccess\Model\ExternalUser
   *   The external authenticated user.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getUser(AccessTokenInterface $accessToken): ExternalUser;

  /**
   * Retrieve the user applications from BusinessAccess.
   *
   * @param string $username
   *   The username for which to retrieve applications.
   * @param bool $external
   *   TRUE if the request comes from the outside of the Menarini network.
   *
   * @return \Drupal\myaccess\Model\ExternalApplication[]
   *   The user applications raw data.
   *
   * @throws \Exception
   */
  public function getExternalApplications(string $username, bool $external): array;

  /**
   * Add MyAccess data to the application.
   *
   * @param string $code
   *   The Application code.
   *
   * @return \Drupal\myaccess\Model\MyAccessData
   *   A new application with myaccess data.
   *
   * @throws \Exception
   */
  public function getMyAccessData(string $code): MyAccessData;

  /**
   * Check if an user can access from outside of the Menarini network.
   *
   * @param string $username
   *   The user username.
   *
   * @return bool
   *   Return TRUE if the username can access from outside of the Menarini
   *   network.
   */
  public function checkLdapExternal(string $username): bool;

}
