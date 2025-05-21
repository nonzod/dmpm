<?php

declare(strict_types=1);

namespace Drupal\myaccess\OpenId;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\myaccess\FunctionalTrait;
use Drupal\myaccess\Model\ExternalApplication;
use Drupal\myaccess\Model\ExternalUser;
use Drupal\myaccess\Model\MyAccessData;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SocialConnect\Common\HttpStack;
use SocialConnect\HttpClient\Request;
use SocialConnect\HttpClient\RequestFactory;
use SocialConnect\HttpClient\StreamFactory;
use SocialConnect\Provider\AccessTokenInterface;
use SocialConnect\Provider\Session\SessionInterface;

/**
 * OpenId Connect Client.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Client implements ClientInterface {

  use FunctionalTrait;

  const EXTERNAL_ACCESS_URL = '%s/api/externalaccess/%s?groups=true';

  const ALL_APPLICATIONS_URL = '%s/api/application/user/all?groups=true&username=%s&external=%s';

  const MY_ACCESS_URL = '%s/api/my-access?code=%s';

  /**
   * The OpenId provider.
   *
   * @var \Drupal\myaccess\OpenId\Provider
   */
  private $provider;

  /**
   * The HTTP client.
   *
   * @var \Psr\Http\Client\ClientInterface
   */
  private $httpClient;

  /**
   * MyAccess settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $settings;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * OidcProvider constructor.
   *
   * @param \SocialConnect\Provider\Session\SessionInterface $session
   *   The Session service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The Config service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Psr\Log\LoggerInterface $logger_http_client
   *   The Logger service dedicated to http_client.
   */
  public function __construct(SessionInterface $session, ConfigFactoryInterface $config, LoggerInterface $logger, LoggerInterface $logger_http_client) {
    $this->settings = $config->get('myaccess.settings');
    $configureProviders = [
      'redirectUri' => (string) $this->settings->get('openid.redirect_uri'),
      'redirectLogoutUri' => (string) $this->settings->get('openid.redirect_logout_uri'),
      'baseUrl' => (string) $this->settings->get('openid.base_uri'),
      'realm' => $this->settings->get('openid.realm'),
      'applicationId' => (string) $this->settings->get('openid.application_id'),
      'applicationSecret' => (string) $this->settings->get('openid.application_secret'),
      'scope' => $this->settings->get('openid.scope'),
    ];

    $this->httpClient = new TraceableCurl([CURLOPT_TIMEOUT_MS => 60 * 1000]);
    $this->httpClient->setLogger($logger_http_client);

    $http_stack = new HttpStack($this->httpClient, new RequestFactory(), new StreamFactory());

    $this->provider = new Provider($http_stack, $session, $configureProviders);
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function makeAuthUrl($destination_uri = NULL): string {
    // Retrieve the auth url.
    $url = $this->provider->makeAuthUrl();

    // Add destination uri in query parameters.
    if (!empty($destination_uri)) {
      $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'destination_uri=' . $destination_uri;
    }

    return $url;
  }

  /**
   * {@inheritDoc}
   */
  public function makeLogoutUrl(): string {
    return $this->provider->makeLogoutUrl();
  }

  /**
   * {@inheritDoc}
   */
  public function getAccessTokenByRequestParameters(array $query): AccessTokenInterface {
    return $this->provider->getAccessTokenByRequestParameters($query);
  }

  /**
   * {@inheritDoc}
   */
  public function getAccessTokenByUserCredentials(string $username, string $password): AccessTokenInterface {
    try {
      return $this->provider->getAccessTokenByUserCredentials($username, $password);
    }
    catch (\Exception | ClientExceptionInterface $e) {
      $this->logger->error('OpenId/Client throw exception in "@method": @message.', [
        '@method' => 'getAccessTokenByUserCredentials',
        '@message' => $e->getMessage(),
      ]);

      return new NullAccessToken();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getOriginalAccessTokenFromGoogle(AccessTokenInterface $accessToken): AccessTokenInterface {
    try {
      return $this->provider->getOriginalAccessTokenFromGoogle($accessToken);
    }
    catch (\Exception | ClientExceptionInterface $e) {
      $this->logger->error('OpenId/Client throw exception in "@method": @message.', [
        '@method' => 'getOriginalAccessTokenFromGoogle',
        '@message' => $e->getMessage(),
      ]);

      return new NullAccessToken();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getOriginalIdTokenFromGoogle(AccessTokenInterface $accessToken): array {
    try {
      return $this->provider->getOriginalIdTokenFromGoogle($accessToken);
    }
    catch (\Exception | ClientExceptionInterface $e) {
      $this->logger->error(sprintf('Exception in getOriginalIdTokenFromGoogle: %s', $e->getMessage()));

      return [];
    }
  }

  public function refreshTokenBySavedRefreshToken(array $query): array {
    return $this->provider->refreshTokenBySavedRefreshToken($query);
  }

  /**
   * {@inheritDoc}
   */
  public function getUser(AccessTokenInterface $accessToken): ExternalUser {
    $identity = $this->provider->getIdentity($accessToken);

    return ExternalUser::fromIdentity($identity);
  }

  /**
   * {@inheritDoc}
   */
  public function getExternalApplications(string $username, bool $external): array {
    try {
      $resource_admin_uri = $this->settings->get('resource_admin_uri');

      $external = FALSE; // Force all applications. See tickets group ID 1

      $url = sprintf(self::ALL_APPLICATIONS_URL, $resource_admin_uri, $username, $external ? 'true' : 'false');
      $response = $this->authorizedRequest($url);

      $status_code = $response->getStatusCode();
      $contents = $response->getBody()->getContents();

      if ($status_code == 200) {
        $this->logger->info('OpenId/Client External Applications OK: @contents', [
          '@status_code' => $status_code,
          '@contents' => $contents,
        ]);
        return ExternalApplication::fromArray(json_decode($contents, TRUE));
      }
      else {
        $this->logger->error('Error retrieving applications: @status_code - @contents.', [
          '@status_code' => $status_code,
          '@contents' => $contents,
        ]);

        throw new \Exception(sprintf('Error retrieving applications: %s - %s',
          $status_code, $contents));
      }
    }
    catch (\Exception $e) {
      $this->logger->error('OpenId/Client throw exception in "@method": @message.', [
        '@method' => 'getExternalApplications',
        '@message' => $e->getMessage(),
      ]);

      throw new \Exception($e->getMessage(), 0, $e);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getMyAccessData(string $code): MyAccessData {
    $resource_admin_uri = $this->settings->get('resource_admin_uri');

    $url = sprintf(self::MY_ACCESS_URL, $resource_admin_uri, rawurlencode($code));
    $response = $this->authorizedRequest($url);

    $status_code = $response->getStatusCode();
    $contents = $response->getBody()->getContents();

    if ($status_code == 200) {
      return MyAccessData::fromArray(json_decode($contents, TRUE));
    }
    else {
      $this->logger->error('Error retrieving applications @code: @status_code - @contents.', [
        '@code' => $code,
        '@status_code' => $status_code,
        '@contents' => $contents,
      ]);

      throw new \Exception(sprintf('Error retrieving application %s: %s - %s',
        $code, $status_code, $contents));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function checkLdapExternal(string $username): bool {
    try {
      $resource_admin_uri = $this->settings->get('resource_admin_uri');

      $url = sprintf(self::EXTERNAL_ACCESS_URL, $resource_admin_uri, $username);
      $response = $this->authorizedRequest($url);

      if ($response->getStatusCode() == 200) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Perform an authorized request to an url.
   *
   * @param string $url
   *   The URL of the request to call with an Authorization header.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @throws \Exception
   */
  private function authorizedRequest(string $url): ResponseInterface {
    try {
      $access_token = $this->provider->getAccessTokenByClientCredentials();

      $request = new Request('GET', $url);
      $token = $access_token->getToken();
      if ($token == NULL) {
        throw new \Exception('Unable to get the access token');
      }

      /** @var \Psr\Http\Message\RequestInterface $auth_request */
      $auth_request = $request->withHeader('Authorization', 'Bearer ' . $token);

      return $this->httpClient->sendRequest($auth_request);
    }
    catch (ClientExceptionInterface $e) {
      throw new \Exception($e->getMessage(), 0, $e);
    }
  }

}
