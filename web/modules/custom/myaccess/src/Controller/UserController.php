<?php

declare(strict_types=1);

namespace Drupal\myaccess\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\OpenId\NullAccessToken;
use Drupal\myaccess\UncacheableRedirectTrait;
use Drupal\myaccess\UserManagerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use SocialConnect\OAuth2\AccessToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for user endpoints.
 */
class UserController extends ControllerBase {

  use UncacheableRedirectTrait;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  private $client;

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  private $userManager;

  /**
   * The Page Cache Kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  private $pageCacheKillSwitch;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): UserController {
    $instance = parent::create($container);

    $client = $container->get('myaccess.oidc_client');
    assert($client instanceof ClientInterface);
    $instance->client = $client;

    $user_manager = $container->get('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);
    $instance->userManager = $user_manager;

    $page_cache_kill_switch = $container->get('page_cache_kill_switch');
    assert($page_cache_kill_switch instanceof KillSwitch);
    $instance->pageCacheKillSwitch = $page_cache_kill_switch;

    return $instance;
  }

  /**
   * Login the user after external authentication.
   *
   * This method retrieve the user data from the OpenId client, register the
   * user if this is the first time and then perform the login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function login(Request $request): RedirectResponse {
    try {
      $access_token = $this->client->getAccessTokenByRequestParameters($request->query->all());
      $user = $this->client->getUser($access_token);

      $this->getLogger('myaccess')
        ->info('Login request for user "@user/@username" (@email).', [
          '@user' => $user->getId(),
          '@username' => $user->getUsername(),
          '@email' => $user->getEmail(),
        ]);

      $this->userManager->loginRegister($user, $access_token);
    }
    catch (ClientExceptionInterface $e) {
      $this->messenger()
        ->addError($this->t('Something wrong with authentication, please try again later'));

      $this->getLogger('myaccess')
        ->error('UserController throw ClientException: @message.', ['@message' => $e->getMessage()]);
    }
    catch (\Throwable $e) {
      $this->getLogger('myaccess')
        ->error('UserController throw exception: @message.', [
          '@message' => $e->getPrevious() != NULL ? $e->getPrevious()
            ->getMessage() : $e->getMessage(),
        ]);
    }

    if(!empty($_SESSION['myaccess_destination_uri'])) {
      $url = $_SESSION['myaccess_destination_uri'];
      unset($_SESSION['myaccess_destination_uri']);
    } else {
      // Filter only 'destination_uri' to query args to pass.
      $query_parameters = $request->query->all();
      $query_parameters = array_filter($query_parameters, function ($key) {
        return $key == "destination_uri";
      }, ARRAY_FILTER_USE_KEY);

      $redir_route_after_login = '<front>';
      //no longer used:
      //$redir_route_after_login = 'myaccess.password_form';

      $url = Url::fromRoute(
        $redir_route_after_login,
        [],
        ['query' => $query_parameters]
      )->toString();
    }
    
    assert(is_string($url));

    return new RedirectResponse($url);
  }

  /**
   * Login the user with access token.
   *
   * This method retrieve the user data from the OpenId client, register the
   * user if this is the first time and then perform the login.
   * Used only for stress test.
   *
   * In query string will be found:
   * - access_token (required)
   * - expires_in/expires
   * - user_id
   * - refresh_token
   * - email
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function loginWithAccessToken(Request $request): RedirectResponse {
    try {
      // Create access token from query information.
      $access_token = new AccessToken($request->query->all());
      // Use access token for retrieve the user.
      $user = $this->client->getUser($access_token);

      $this->getLogger('myaccess')
        ->info('Login request with access-token for user "@user/@username" (@email).', [
          '@user' => $user->getId(),
          '@username' => $user->getUsername(),
          '@email' => $user->getEmail(),
        ]);

      $this->userManager->loginRegister($user);
    }
    catch (ClientExceptionInterface $e) {
      $this->messenger()
        ->addError($this->t('Something wrong with authentication, please try again later'));
      $this->getLogger('myaccess')->error($e->getMessage());
    }
    catch (\Throwable $e) {
      $this
        ->getLogger('myaccess')
        ->error(
          sprintf(
            'Exception during login handler: %s',
            $e->getPrevious() != NULL ? $e->getPrevious()
              ->getMessage() : $e->getMessage()
          )
        );
    }

    $url = Url::fromRoute('myaccess.password_form')->toString();
    assert(is_string($url));

    // Not cache the redirect response.
    $this->pageCacheKillSwitch->trigger();

    return new RedirectResponse($url);
  }

  /**
   * Logout the user.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A trusted redirect response.
   *
   * @psalm-suppress UndefinedFunction
   */
  public function logout(): TrustedRedirectResponse {
    user_logout();

    $logout_url = $this->client->makeLogoutUrl();

    return $this->buildUncacheableRedirect($logout_url);
  }

  /**
   * Render the blocked page.
   *
   * @return string[]
   *   A render array for the blocked page.
   */
  public function blocked(): array {
    return [
      '#theme' => 'blocked',
    ];
  }

  public function loginForce() {
    if($this->currentUser()->isAuthenticated()) {
      $redir = Url::fromRoute('<front>')->toString(true)->getGeneratedUrl();
      return $this->buildUncacheableRedirect($redir);
    } else {
      return [
        '#markup' => $this->t('Authenticating...'),
      ];
    }
  }

  public function passwordSessionForm() {
    $response = new HtmlResponse();
    $html = '<form id="password-session-form">
        <h2>'.t('Password Session Save').'</h2>
        <div class="form-item-password">
          <label for="password-session--input" class="form-required">'.t('Password').'<span class="form-required" title="This field is required">*</span></label>
          <input type="password" name="password-session--input" id="password-session--input" class="form-text required form-control">
          <div id="password-session--description" class="help-block">
            '.t('Use your email account password').'
          </div>
        </div>
        <button type="submit" class="form-submit btn-primary" disabled>'.t('Save password').'</button>
      </form><div class="feedback-ok"><h2>'.t('Password saved successfully').'</h2><div>'.t('The password you saved will be used, for this session, to allow your automatic login on some external apps').'</div></div>';
    $icon_close = '<svg class="icon icon-close"><use xlink:href="#icon-close"></use></svg>';
    $html = '<div id="password-session-form-wrapper"><div id="password-session-form-popover">'.$html.$icon_close.'</div></div>';
    $response->setContent($html);
    return $response;
  }

  public function passwordSessionSave(Request $request) {
    $username = $this->userManager->getUsername();
    $password = $request->request->get('pwd');

    $return = ['status' => 'error', 'message' => 'Invalid data'];
    if(!empty($username) && !empty($password) && $request->getMethod() === 'POST') {
      $access_token = $this->client->getAccessTokenByUserCredentials($username, $password);
      /** @var  $sessionManager \Drupal\myaccess\SessionManager  */
      $sessionManager = \Drupal::service('myaccess.session_manager');
      $session_data = $sessionManager->getAll();
      if ($access_token instanceof NullAccessToken) {
        $new_session_data = $session_data->deletePassword();
        $return = ['status' => 'error', 'message' => 'Invalid password'];
      } else {
        $new_session_data = $session_data->withPassword($password);
        $return = ['status' => 'ok', 'message' => 'Valid password'];
      }
      $sessionManager->save($new_session_data);
    }
    return new JsonResponse($return);
  }

}
