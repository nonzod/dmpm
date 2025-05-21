<?php

declare(strict_types=1);

namespace Drupal\myaccess\OpenId;

use Psr\Http\Message\RequestInterface;
use SocialConnect\Common\HttpStack;
use SocialConnect\JWX\DecodeOptions;
use SocialConnect\JWX\JWT;
use SocialConnect\OAuth2\AccessToken;
use SocialConnect\OpenIDConnect\Provider\Keycloak;
use SocialConnect\Provider\AccessTokenInterface;
use SocialConnect\Provider\Exception\InvalidAccessToken;
use SocialConnect\Provider\Session\SessionInterface;

use SocialConnect\Provider\Exception\InvalidResponse;
use SocialConnect\OpenID\Exception\Unauthorized;
use Drupal\myaccess\OpenId\KeycloakAccessToken;


/**
 * OpenId Connect provider for Menarini IDM.
 *
 * @package Drupal\myaccess\OpenId
 */
class Provider extends Keycloak {

  const NAME = 'menarini_idm';

  /**
   * The url to redirect the user after logout.
   *
   * @var string
   */
  protected $redirectLogoutUri;

  /**
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $user_manager;

  /**
   * Provider constructor.
   *
   * @param \SocialConnect\Common\HttpStack $httpStack
   *   The HTTP stack to perform calls.
   * @param \SocialConnect\Provider\Session\SessionInterface $session
   *   The session to store data.
   * @param array $parameters
   *   Configuration parameters.
   */
  public function __construct(HttpStack $httpStack, SessionInterface $session, array $parameters) {
    parent::__construct($httpStack, $session, $parameters);
    $this->redirectLogoutUri = rtrim($this->getRequiredStringParameter('redirectLogoutUri', $parameters), '/') . '/';
  }

  /**
   * Parse access token from response's $body
   *
   * @param string $body
   * @return KeycloakAccessToken
   * @throws InvalidAccessToken
   */
  public function parseToken(string $body)
  {
    if (empty($body)) {
      throw new InvalidAccessToken('Provider response with empty body');
    }

    $token = json_decode($body, true);
    if ($token) {
      if (!is_array($token)) {
        throw new InvalidAccessToken('Response must be array');
      }

      $token_obj = new KeycloakAccessToken($token);

      $token_obj->setJwt(
        JWT::decode($token['id_token'], $this->getJWKSet(), new DecodeOptions())
      );
      return $token_obj;
    }

    throw new InvalidAccessToken('Server response with not valid/empty JSON');
  }

  /**
   * Construct the logout url.
   *
   * @return string
   *   The logout url.
   */
  public function makeLogoutUrl(): string {
    return $this->getLogoutUrl() . '?redirect_uri=' . $this->redirectLogoutUri;
  }

  /**
   * Return the Access Token from username and password.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface
   *   An OAuth2 Access Token.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getAccessTokenByUserCredentials(string $username, string $password): AccessTokenInterface {
    $response = $this->executeRequest(
      $this->makeAccessTokenRequestByUserCredentials($username, $password)
    );

    return $this->parseOauth2Token($response->getBody()->getContents());
  }

  /**
   * Retrieve the token that Google returns to Keycloak after authentication.
   *
   * @param \SocialConnect\Provider\AccessTokenInterface $accessToken
   *   The Keycloak Access Token.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface
   *   The token that Google returns to Keycloak after authentication.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getOriginalAccessTokenFromGoogle(AccessTokenInterface $accessToken): AccessTokenInterface {
    $response = $this->executeRequest(
      $this->makeGoogleAccessTokenRequestByKeyCloakAccessToken($accessToken)
    );

    return $this->parseOauth2Token($response->getBody()->getContents());
  }

  /**
   * Retrieve the id_token that Google returns to Keycloak after authentication.
   *
   * @param \SocialConnect\Provider\AccessTokenInterface $accessToken
   *   The Keycloak Access Token.
   *
   * @return array
   *   The id_token that Google returns to Keycloak after authentication.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getOriginalIdTokenFromGoogle(AccessTokenInterface $accessToken): array {
    $response = $this->executeRequest(
      $this->makeGoogleAccessTokenRequestByKeyCloakAccessToken($accessToken)
    );

    $response = $response->getBody()->getContents();
    $decoded = json_decode($response, TRUE);
    $parts = explode('.', $decoded['id_token']);

    return json_decode(JWT::urlsafeB64Decode($parts[1]), TRUE);
  }

  /**
   * Return the Access Token from client credentials.
   *
   * @return \SocialConnect\Provider\AccessTokenInterface
   *   An OAuth2 Access Token.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getAccessTokenByClientCredentials(): AccessTokenInterface {
    $response = $this->executeRequest(
      $this->makeAccessTokenRequestByClientCredentials()
    );

    return $this->parseOauth2Token($response->getBody()->getContents());
  }

  /**
   * Return the url to logout the user.
   *
   * @return string
   *   The url to logout the user.
   */
  protected function getLogoutUrl() {
    return $this->getBaseUri() . sprintf('realms/%s/protocol/%s/logout',
        $this->realm, $this->protocol);
  }

  /**
   * Construct a request to retrieve an Access Token from username and password.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The new request.
   */
  protected function makeAccessTokenRequestByUserCredentials(string $username, string $password): RequestInterface {
    $parameters = [
      'client_id' => $this->consumer->getKey(),
      'client_secret' => $this->consumer->getSecret(),
      'username' => $username,
      'password' => $password,
      'grant_type' => 'password',
    ];

    return $this->httpStack->createRequest($this->requestHttpMethod,
      $this->getRequestTokenUri())
      ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
      ->withBody($this->httpStack->createStream(http_build_query($parameters, '', '&')));
  }

  /**
   * Construct a request to retrieve an Access Token from client credentials.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The new request.
   */
  protected function makeAccessTokenRequestByClientCredentials(): RequestInterface {
    $parameters = [
      'client_id' => $this->consumer->getKey(),
      'client_secret' => $this->consumer->getSecret(),
      'grant_type' => 'client_credentials',
    ];

    return $this->httpStack->createRequest($this->requestHttpMethod,
      $this->getRequestTokenUri())
      ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
      ->withBody($this->httpStack->createStream(http_build_query($parameters, '', '&')));
  }

  public function refreshTokenBySavedRefreshToken(array $saved_token_data): array {
    $parameters = [
      'client_id' => $this->consumer->getKey(),
      'client_secret' => $this->consumer->getSecret(),
      'grant_type' => 'refresh_token',
      'refresh_token' => $saved_token_data['refresh_token'],
    ];

    $request = $this->httpStack->createRequest($this->requestHttpMethod,
      $this->getRequestTokenUri())
      ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
      ->withBody($this->httpStack->createStream(http_build_query($parameters, '', '&')));

    $response = $this->executeRequest($request);
    $response = $response->getBody()->getContents();
    $decoded = json_decode($response, TRUE);
    return $decoded;
  }

  /**
   * Construct a request to retrieve the Google Access Token.
   *
   * @param \SocialConnect\Provider\AccessTokenInterface $accessToken
   *   The Keycloak Access Token.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The new request.
   */
  protected function makeGoogleAccessTokenRequestByKeyCloakAccessToken(AccessTokenInterface $accessToken): RequestInterface {
    return $this->httpStack->createRequest('GET',
      $this->getGoogleBrokerUri())
      ->withAddedHeader('Authorization', 'Bearer ' . $accessToken->getToken());
  }

  /**
   * Parse the response to create an OAuth2 Access Token.
   *
   * This method is copied from SocialConnect\OAuth2\AbstractProvider to avoid
   * to have to different providers, one for OpenID Connect and one for OAuth2.
   *
   * @param string $body
   *   The response body.
   *
   * @return \SocialConnect\OAuth2\AccessToken
   *   An OAuth2 Access Token.
   */
  protected function parseOauth2Token(string $body) {
    if (empty($body)) {
      throw new InvalidAccessToken('Provider response with empty body');
    }

    $token = json_decode($body, TRUE);
    if ($token) {
      if (!is_array($token)) {
        throw new InvalidAccessToken('Response must be array');
      }

      return new AccessToken($token);
    }

    throw new InvalidAccessToken('Server response with not valid/empty JSON');
  }

  /**
   * Return the Keycloak endpoint used to retrieve the Google Access token.
   *
   * @return string
   *   The Keycloak endpoint used to retrieve the Google Access token.
   */
  protected function getGoogleBrokerUri(): string {
    return $this->getBaseUri() . sprintf('realms/%s/broker/google/token', $this->realm);
  }

}
