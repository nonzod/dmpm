<?php

declare(strict_types=1);

namespace Drupal\myaccess\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\myaccess\CookieTrait;
use Drupal\myaccess\Event\UserEvents;
use Drupal\myaccess\Event\UserPostReinsertPasswordEvent;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\OpenId\NullAccessToken;
use Drupal\myaccess\SessionManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form for re-insert the password after the external login.
 *
 * @package Drupal\myaccess\Form
 */
class PasswordForm extends FormBase {

  use CookieTrait;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  protected $client;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Session manager service.
   *
   * @var \Drupal\myaccess\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): PasswordForm {
    $instance = parent::create($container);

    $session = $container->get('myaccess.session_manager');
    assert($session instanceof SessionManagerInterface);
    $instance->sessionManager = $session;

    $client = $container->get('myaccess.oidc_client');
    assert($client instanceof ClientInterface);
    $instance->client = $client;

    $user_manager = $container->get('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);
    $instance->userManager = $user_manager;

    $event_dispatcher = $container->get('event_dispatcher');
    assert($event_dispatcher instanceof EventDispatcherInterface);
    $instance->eventDispatcher = $event_dispatcher;

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'myaccess_password';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Log in'),
    ];

    $form['password'] = [
      '#title' => $this->t('Password'),
      '#type' => 'password',
      '#placeholder' => $this->t('Insert Password'),
      '#description' => $this->t('Use your email account password'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $username = $this->userManager->getUsername();
    $password = $form_state->getValue('password');

    // Retrieve the access token by user credentials.
    $access_token = $this->client->getAccessTokenByUserCredentials($username, $password);
    $form_state->set('access_token', $access_token);

    if ($access_token instanceof NullAccessToken) {
      $form_state->setErrorByName('password', $this->t('Invalid password.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $username = $this->userManager->getUsername();
    $password = $form_state->getValue('password');
    $external = $this->userManager->isExternal();

    // Retrieve and save data in current session.
    $session_data = $this->sessionManager->getAll();
    $new_session_data = $session_data->withPassword($password);
    $this->sessionManager->save($new_session_data);

    // Dispatch event.
    $event = new UserPostReinsertPasswordEvent($this->userManager->getCurrentDrupalUser(), $form_state->get('access_token'));
    $this->eventDispatcher->dispatch(UserEvents::POST_REINSERT_PASSWORD, $event);

    // Build and return response.
    $response = $this->buildResponse($external, $username, $password);
    $form_state->setResponse($response);
  }

  /**
   * Build a response to return to the client after the form submission.
   *
   * @param bool $is_external
   *   TRUE if the request is external.
   * @param string $username
   *   The user username.
   * @param string $password
   *   The user password.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return to the client after the form submission.
   */
  protected function buildResponse(bool $is_external, string $username, string $password): Response {
    $response = new RedirectResponse($this->getRedirectUrl());

    // Set a cookies only if user can access from outside the Menarini network.
    if (!$this->client->checkLdapExternal($username)) {
      return $response;
    }

    // If the request is external, we need to set a cookie to be used by the
    // applications to allow access.
    if ($is_external) {
      $response = $this->withJwtCookies($response, $username, $password);
    }

    return $response;
  }

  /**
   * Get redirect URL.
   *
   * @return string
   *   The redirect url. If defined in query string 'destination_uri', use it.
   */
  protected function getRedirectUrl(): string {
    // Default redirect to front page.
    $url = Url::fromRoute('<front>');

    try {
      // Use the 'destination_uri' for custom redirect.
      if ($this->getRequest()->query->has('destination_uri')) {
        $url = Url::fromUserInput(
          $this->getRequest()->query->get('destination_uri')
        );
      }
    }
    catch (\Throwable $exception) {
      // Nothing.
    }

    return (string) $url->toString();
  }

}
