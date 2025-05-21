<?php

namespace Drupal\myaccess_mocks\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a MyAccess mocks form.
 *
 * @package Drupal\myaccess_mocks\Form
 */
class LoginForm extends FormBase {

  /**
   * The expirable key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * The Profile storage service.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected ProfileStorageInterface $profileStorage;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    $user_storage = $entity_type_manager->getStorage('user');
    assert($user_storage instanceof UserStorageInterface);
    $instance->userStorage = $user_storage;

    $profile_storage = $entity_type_manager->getStorage('profile');
    assert($profile_storage instanceof ProfileStorageInterface);
    $instance->profileStorage = $profile_storage;

    $user_auth = $container->get('user.auth');
    assert($user_auth instanceof UserAuthInterface);
    $instance->userAuth = $user_auth;

    $key_value_exp_factory = $container->get('keyvalue.expirable');
    assert($key_value_exp_factory instanceof KeyValueExpirableFactoryInterface);
    $key_value_exp = $key_value_exp_factory->get('myaccess_mocks');
    assert($key_value_exp instanceof KeyValueStoreExpirableInterface);
    $instance->keyValueExpirable = $key_value_exp;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myaccess_mocks_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#size' => 60,
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t('Enter your username.'),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#description' => $this->t('Enter the password that accompanies your username.'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('name') && user_is_blocked($form_state->getValue('name'))) {
      // Blocked in user administration.
      $form_state->setErrorByName('name', $this->t('The username %name has not been activated or is blocked.', ['%name' => $form_state->getValue('name')]));

      return;
    }

    $accounts = $this->userStorage->loadByProperties([
      'name' => $form_state->getValue('name'),
      'status' => 1,
    ]);
    $account = reset($accounts);
    if ($account instanceof UserInterface) {
      $password = trim($form_state->getValue('pass'));
      if (!$form_state->isValueEmpty('name') && strlen($password) > 0
        && $uid = $this->userAuth->authenticate($form_state->getValue('name'), $password)
      ) {
        $form_state->set('uid', $uid);

        return;
      }
    }

    $user_input = $form_state->getUserInput();
    $query = isset($user_input['name']) ? ['name' => $user_input['name']] : [];
    $form_state->setErrorByName('name', $this->t('Unrecognized username or password. <a href=":password">Forgot your password?</a>', [
      ':password' => Url::fromRoute(
        'user.pass', [], ['query' => $query]
      )->toString(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($uid = $form_state->get('uid'))) {
      return;
    }
    $account = $this->userStorage->load($uid);
    assert($account instanceof UserInterface);

    $access_token = [
      'id' => $uid,
      'firstname' => $account->get('name')->value,
      'lastname' => $account->get('name')->value,
      'email' => $account->getEmail(),
      'username' => $account->get('name')->value,
    ];

    $profile = $this->profileStorage->loadByUser($account, 'profile');
    if ($profile instanceof ProfileInterface) {
      $access_token['firstname'] = $profile->get('field_profile_first_name')->value;
      $access_token['lastname'] = $profile->get('field_profile_last_name')->value;
    }

    $code = (new Random())->name(8);
    $token_data = [
      'access_token' => serialize($access_token),
      'user_id' => $account->id(),
      'email' => $account->getEmail(),
      'id_token' => $code,
    ];

    // Save access token data.
    $this->keyValueExpirable->setWithExpire($code, $token_data, 60 * 5);

    if ($this->getRequest()->query->has('redirect_uri')) {
      // Use specific redirect.
      $url = Url::fromUserInput($this->getRequest()->query->get('redirect_uri'));
      $url->setOption('query', (array) $url->getOption('query') + ['code' => $code]);
      $form_state->setRedirectUrl($url);
    }
    else {
      // Default redirect.
      $form_state->setRedirect(
        'myaccess.login',
        [],
        ['query' => ['code' => $code]]
      );
    }
  }

}
