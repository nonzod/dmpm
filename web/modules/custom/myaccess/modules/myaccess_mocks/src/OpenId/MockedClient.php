<?php

namespace Drupal\myaccess_mocks\OpenId;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Url;
use Drupal\myaccess\Model\ExternalApplication;
use Drupal\myaccess\Model\ExternalUser;
use Drupal\myaccess\Model\MyAccessData;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\OpenId\NullAccessToken;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use SocialConnect\Common\Entity\User;
use SocialConnect\JWX\JWT;
use SocialConnect\OpenIDConnect\AccessToken;
use SocialConnect\Provider\AccessTokenInterface;

/**
 * Mocked version of the OpenId Connect Client.
 *
 * @package Drupal\myaccess_mocks\OpenId
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MockedClient implements ClientInterface {

  /**
   * The expirable key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * Contains the path for folder that contain the mocks data.
   *
   * @var string
   */
  protected $pathMocksData;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Construct new MockedClient instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user auth.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_valye_exp_factory
   *   The key value exp factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    UserAuthInterface $user_auth,
    KeyValueExpirableFactoryInterface $key_valye_exp_factory,
    FileSystemInterface $file_system,
    ModuleExtensionList $extension_list_module
  ) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->userAuth = $user_auth;
    $this->keyValueExpirable = $key_valye_exp_factory->get('myaccess_mocks');
    $this->pathMocksData = $file_system->realpath(
        $extension_list_module->getPath('myaccess_mocks')
      ) . '/';
  }

  /**
   * {@inheritDoc}
   */
  public function makeAuthUrl($destination_uri = NULL): string {

    // Retrieve the redirect uri.
    // todo: use the value of 'openid.redirect_uri' in configuration 'myaccess.settings'.
    $redirect_uri = '/oidc/login';

    // Add destination uri in query parameters.
    if (!empty($destination_uri)) {
      $redirect_uri .= (parse_url($redirect_uri, PHP_URL_QUERY) ? '&' : '?') . 'destination_uri=' . $destination_uri;
    }

    $url = (string) Url::fromRoute('myaccess_mocks.login')->toString();
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'redirect_uri=' . $redirect_uri;

    return $url;
  }

  /**
   * {@inheritDoc}
   */
  public function makeLogoutUrl(): string {
    return '/';
  }

  /**
   * {@inheritDoc}
   */
  public function getAccessTokenByRequestParameters(array $query): AccessTokenInterface {
    $token_data = $this->keyValueExpirable->get($query['code']);

    return new AccessToken($token_data);
  }

  /**
   * {@inheritDoc}
   */
  public function getAccessTokenByUserCredentials(string $username, string $password): AccessTokenInterface {

    // Load the user from username and password.
    $accounts = $this->userStorage->loadByProperties([
      'name' => $username,
      'status' => 1,
    ]);
    $account = reset($accounts);

    if (!$account instanceof UserInterface) {
      return new NullAccessToken();
    }

    // Try if password is correct!.
    if ($this->userAuth->authenticate($username, $password) === FALSE) {
      return new NullAccessToken();
    }

    // Create new access token.
    $access_token = [
      'id' => $account->id(),
      'firstname' => (new Random())->name(8),
      'lastname' => (new Random())->name(12),
      'email' => $account->getEmail(),
      'username' => $account->get('name')->value,
    ];

    $token_data = [
      'access_token' => serialize($access_token),
      'user_id' => $account->id(),
      'email' => $account->getEmail(),
      'id_token' => (new Random())->name(12),
    ];

    return new AccessToken($token_data);
  }

  /**
   * {@inheritDoc}
   */
  public function getUser(AccessTokenInterface $accessToken): ExternalUser {

    // Unserialize and retrieve data from dummy access token.
    $data = unserialize($accessToken->getToken());

    $identity = new User();
    $identity->id = $data['id'];
    $identity->firstname = $data['firstname'];
    $identity->lastname = $data['lastname'];
    $identity->email = $data['email'];
    $identity->emailVerified = FALSE;
    $identity->username = $data['username'];

    return ExternalUser::fromIdentity($identity);
  }

  /**
   * {@inheritDoc}
   */
  public function getExternalApplications(string $username, bool $external): array {
    $folders = [$this->pathMocksData, 'private://', 'public://'];

    foreach ($folders as $path_folder) {
      $applications_filename = sprintf('%smocks/%s-%s-applications.json', $path_folder, $username, $external ? 'ext' : 'int');
      if (file_exists($applications_filename)) {
        $applications_data = json_decode(file_get_contents($applications_filename), TRUE);

        return ExternalApplication::fromArray($applications_data);
      }
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getMyAccessData(string $code): MyAccessData {
    $myaccess_filename = sprintf('%s/mocks/myaccess/%s.json', $this->pathMocksData, $code);

    if (file_exists($myaccess_filename)) {
      return MyAccessData::fromArray(
        json_decode(file_get_contents($myaccess_filename), TRUE)
      );
    }

    throw new \Exception(sprintf('Error retrieving application %s', $code));
  }

  /**
   * {@inheritDoc}
   */
  public function checkLdapExternal(string $username): bool {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getOriginalAccessTokenFromGoogle(AccessTokenInterface $accessToken): AccessTokenInterface {
    return new NullAccessToken();
  }

  /**
   * {@inheritDoc}
   */
  public function getOriginalIdTokenFromGoogle(AccessTokenInterface $accessToken): array {
    $response = <<<TOKEN
TOKEN;

    $decoded = json_decode($response, TRUE);
    $parts = explode('.', $decoded['id_token']);

    return json_decode(JWT::urlsafeB64Decode($parts[1]), TRUE);
  }

}
