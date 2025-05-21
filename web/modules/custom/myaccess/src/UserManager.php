<?php

//@msg_clean

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\myaccess\Entity\Application;
use Drupal\myaccess\Entity\ApplicationInterface;
use Drupal\myaccess\Exception\GroupNotCreatedException;
use Drupal\myaccess\Exception\LoginNotAllowedException;
use Drupal\myaccess\Exception\UpdateUserGroupsException;
use Drupal\myaccess\Model\ExternalUser;
use Drupal\myaccess\Model\HmrsUserData;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\StackMiddleware\IsExternalMiddleware;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use SocialConnect\Provider\AccessTokenInterface;
use Drupal\myaccess\OpenId\KeycloakAccessToken;

/**
 * Manage users.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class UserManager implements UserManagerInterface {

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The External authentication service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected ExternalAuthInterface $externalAuth;

  /**
   * The Group manager service.
   *
   * @var \Drupal\myaccess\GroupManagerInterface
   */
  protected GroupManagerInterface $groupManager;

  /**
   * The Group storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $groupStorage;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Client service.
   *
   * @var \Drupal\myaccess\OpenId\ClientInterface
   */
  protected $openIdClient;

  /**
   * The Profile storage service.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected ProfileStorageInterface $profileStorage;

  /**
   * The Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The Session manager service.
   *
   * @var \Drupal\myaccess\SessionManagerInterface
   */
  protected SessionManagerInterface $sessionManager;

  /**
   * The term storage service.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The User storage service.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * The encryption key.
   * @TODO: Move to settings?
   * @var string
   */
  protected $encryptionKey = 'n2jy89NATwj7wdZmx9Jj628mpJau1WEs';
  /**
   * The encryption cipher.
   * @var string
   */
  protected $encryptionCipher = "AES-256-CBC";

  /**
   * UserManager constructor.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The External authentication service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The User storage service.
   * @param \Drupal\profile\ProfileStorageInterface $profile_storage
   *   The Profile storage service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current logged in user.
   * @param \Drupal\myaccess\SessionManagerInterface $session_manager
   *   The Session manager service.
   * @param \Drupal\myaccess\GroupManagerInterface $group_manager
   *   The Group manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The Request stack service.
   * @param \Drupal\myaccess\OpenId\ClientInterface $openid_client
   *   The OpenId client service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  final public function __construct(
    ExternalAuthInterface $external_auth,
    UserStorageInterface $user_storage,
    ProfileStorageInterface $profile_storage,
    AccountProxyInterface $current_user,
    SessionManagerInterface $session_manager,
    GroupManagerInterface $group_manager,
    RequestStack $requestStack,
    ClientInterface $openid_client,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    UserDataInterface $user_data
  ) {
    $this->externalAuth = $external_auth;
    $this->userStorage = $user_storage;
    $this->profileStorage = $profile_storage;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
    $this->groupManager = $group_manager;
    $this->requestStack = $requestStack;
    $this->openIdClient = $openid_client;
    $this->logger = $logger;
    $this->groupStorage = $entity_type_manager->getStorage('group');
    $this->userData = $user_data;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritDoc}
   */
  public function getTermsIdNavigationThatUserIsEditor(AccountInterface $account): array {
    // Retrieve the taxonomy where the current user is "editor".
    $results_id = $this->termStorage
      ->getQuery()
      ->condition('vid', 'navigation')
      ->condition('field_navigation_editors', $account->id(), '=')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($results_id)) {
      return [];
    }

    // Exclude the first and second level of vocabulary.
    $terms_1_2_level = $this->termStorage
      ->loadTree('navigation', 0, 2, FALSE);
    $terms_1_2_level_ids = [];
    array_walk($terms_1_2_level, function ($term_object) use (&$terms_1_2_level_ids) {
      $terms_1_2_level_ids[$term_object->tid] = $term_object->tid;
    });

    return array_diff($results_id, $terms_1_2_level_ids);
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress NoInterfaceProperties
   */
  public function loginRegister(ExternalUser $externalUser, KeycloakAccessToken $oidc_data): void {

    $username = $externalUser->getUsername();
    $provider = 'myaccess';

    $account = $this->externalAuth->load($username, $provider);
    if ($account == NULL) {
      $account = $this->externalAuth->register(
        $username,
        $provider,
        [
          'name' => $externalUser->getUsername(),
          'mail' => $externalUser->getEmail(),
        ],
        [
          'oid' => $externalUser->getId(),
        ]);
    }

    // If the 'mail' has been changed, will be updated it.
    if ($account->getEmail() != $externalUser->getEmail()) {

      // Arguments for logger message.
      $args = [
        '@user_mail' => $account->getEmail(),
        '@external_user_name' => $externalUser->getUsername(),
        '@external_user_mail' => $externalUser->getEmail(),
      ];

      // Set the new "mail" for user.
      $account->setEmail($externalUser->getEmail());
      $account->save();

      $this->logger
        ->info("The mail of user \"@external_user_name\" (@user_mail) has been updated with new value \"@external_user_mail\" .", $args);
    }

    $profile = $this->profileStorage->loadByUser($account, 'profile');
    if ($profile instanceof ProfileInterface
      && $profile->get('field_profile_first_name')->value != $externalUser->getFirstname()
      && $profile->get('field_profile_last_name')->value != $externalUser->getLastname()) {

      $profile->set('field_profile_first_name', $externalUser->getFirstname());
      $profile->set('field_profile_last_name', $externalUser->getLastname());
      $profile->save();
    }

    // save OIDC Token Account Data
    $oidc_data_array = [
      'token' => $oidc_data->getToken(),
      'refresh_token' => $oidc_data->getRefreshToken(),
      'expires' => $oidc_data->getExpires(),
      'refresh_expires' => $oidc_data->getRefreshExpires(),
    ];

    $uid = (int)$account->id();

    $ok_save = $this->saveTokenInfo($uid, $oidc_data_array);

    $this->userData->set('myaccess', (int) $account->id(), 'is_oidc_user', 1);

    // Finalize login.
    $this->externalAuth->userLoginFinalize($account, $username, $provider);
  }

  /**
   * {@inheritDoc}
   */
  public function saveTokenInfo(int $uid, array $oidc_data_array): bool {
    try{
      $this->userData->set('myaccess', $uid, 'oidc_data', $this->tokenDataEncrypt($oidc_data_array));
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Error saving token info for user "@uid": @message.', [
        '@uid' => $uid,
        '@message' => $e->getMessage(),
      ]);
      return false;
    }
  }

  public function getSavedTokenInfo(int $uid): array {
    $data = $this->userData->get('myaccess', $uid, 'oidc_data');
    if($data == NULL) {
      return [];
    }
    return $this->tokenDataDecrypt($data);
  }

  protected function tokenDataEncrypt(array $data): string {
    $data = json_encode($data);
    $iv_length = openssl_cipher_iv_length($this->encryptionCipher);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $ciphertext = openssl_encrypt($data, $this->encryptionCipher, $this->encryptionKey, 0, $iv);
    $data_encrypted = base64_encode($iv . $ciphertext);
    return $data_encrypted;
  }

  protected function tokenDataDecrypt(string $enc_data): array {
    $decoded_b64 = base64_decode($enc_data);
    $iv_length = openssl_cipher_iv_length($this->encryptionCipher);
    $iv_dec = substr($decoded_b64, 0, $iv_length);
    $ciphertext_dec = substr($decoded_b64, $iv_length);

    $decrypted = openssl_decrypt($ciphertext_dec, $this->encryptionCipher, $this->encryptionKey, 0, $iv_dec);
    return (array) json_decode($decrypted, TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function getCurrentDrupalUser(): UserInterface {
    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->userStorage->load($this->currentUser->id());

    return $current_user;
  }

  /**
   * {@inheritDoc}
   */
  public function getUsername(): string {
    return $this->getCurrentDrupalUser()->getAccountName();
  }

  /**
   * {@inheritDoc}
   */
  public function getPassword(): string {
    $session_data = $this->sessionManager->getAll();

    return $session_data->getDecryptedPassword();
  }

  /**
   * {@inheritDoc}
   */
  public function isExternal(): bool {
    $request = $this
      ->requestStack
      ->getCurrentRequest();

    if ($request == NULL) {
      return TRUE;
    }

    // See \Drupal\myaccess\StackMiddleware\IsExternalMiddleware::handle().
    return $request->attributes->get(IsExternalMiddleware::KEY, TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function checkAccessExternal(): bool {
    $user = $this->getCurrentDrupalUser();
    if ($user->isAnonymous()) {
      return FALSE;
    }

    // Use a static cache for reduce external calls in same request.
    $data = &drupal_static('UserManager__checkAccessExternal');

    $username = $user->getAccountName();
    if (!isset($data[$username])) {
      $data[$username] = $this->openIdClient->checkLdapExternal($username);
    }

    return $data[$username];
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function attachApplications(UserInterface $user, array $applications): void {
    if (!$user->hasField('field_applications')) {
      return;
    }

    // Retrieve the field.
    $field = $user->get('field_applications');

    // Preparare the variables for store the application attached or removed.
    $app_added = [];
    $app_removed = [];
    $apps = [];

    // Build an array of application keyed for id.
    /** @var \Drupal\myaccess\Entity\ApplicationInterface $application */
    foreach ($applications as $application) {
      $apps[(int) $application->id()] = $application;
    }

    // Filter applications not dedicated to user.
    // Only items for which the callback returns TRUE are preserved.
    $field->filter(
      function ($item) use ($apps, &$app_removed) {
        if (in_array($item->target_id, array_keys($apps))) {
          return TRUE;
        }
        $app_removed[] = $item->target_id;

        return FALSE;
      }
    );

    // Add new applications to user.
    $values = array_column($field->getValue(), 'target_id');
    foreach ($applications as $application) {
      if (!in_array($application->id(), $values)) {
        $field->appendItem(['target_id' => $application->id()]);
        $app_added[] = $application->id();
      }
    }

    if (!empty($app_added) || !empty($app_removed)) {

      // Save in log the application attached or removed for user.
      $this->logger->debug("Attached/removed applications for user %user: \"%app_added\" added, \"%app_removed\" removed.", [
        '%apps' => [],
        '%app_added' => count($app_added) > 0 ? 'App ids: ' . implode(',', $app_added) : 'none',
        '%app_removed' => count($app_removed) > 0 ? 'App ids: ' . implode(',', $app_removed) : 'none',
      ]);

      // Save only if updates.
      $user->save();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function hasApplication(AccountInterface $account, ApplicationInterface $application): bool {
    // Google applications are available for everybody.
    if ($application->getType() == Application::GOOGLE) {
      return TRUE;
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($account->id());
    if ($user == NULL) {
      return FALSE;
    }

    $applications = $user->get('field_applications')->getValue();
    foreach ($applications as $user_application) {
      if ($user_application['target_id'] == $application->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function updateData(UserInterface $user, ?HmrsUserData $userData): void {
    // Block user if no data was found.
    if ($userData == NULL) {
      $user->block();
      $user->save();

      throw new LoginNotAllowedException($user->getEmail() ?? 'no-email', 'No records found for this user');
    }

    $groups = $userData->getRecords();
    $external = $userData->isExternal();
    $manager = $userData->isManager();
    $position_titles = $userData->getPositionTitles();

    $groupsToAdd = $this->groupManager->getGroupsToAdd($user, $groups, $external, $manager);
    $groupsToRemove = $this->groupManager->getGroupsToRemove($user, $groups, $external, $manager);

    $this->ensureGroupsExist($groupsToAdd);

    try {
      foreach ($groupsToAdd as $group) {
        $this->groupManager->addUserToGroup($user, $group['name']);
      }

      foreach ($groupsToRemove as $group) {
        $this->groupManager->removeUserFromGroup($user, $group['name']);
      }

      // Add users to the correct internal or external role.
      $external ? $user->addRole('foe') : $user->removeRole('foe');

      // Set the field_position_title field.
      $user->set('field_position_title', $position_titles);

      // Ensure the user is active.
      $user->activate();

      // Save the user.
      $user->save();
    }
    catch (\Exception $e) {
      throw new UpdateUserGroupsException($e);
    }

    // Store extra data for other service.
    // Example: "self::getGroupScopeForUser" or myportal_weather module.
    $this->userData->set('myaccess', (int) $user->id(), 'groups_scope', $userData->getRecords());
  }

  /**
   * {@inheritDoc}
   *
   * @psalm-suppress NoInterfaceProperties
   */
  public function updateUserPicture(string $picture): void {
    $user = $this->getCurrentDrupalUser();

    $profile = $this->profileStorage->loadByUser($user, 'profile');
    if ($profile instanceof ProfileInterface
      && $profile->hasField('field_google_picture')
      && $profile->get('field_google_picture')->value != $picture) {

      $profile->set('field_google_picture', $picture);
      $profile->save();

      $this->logger->debug('User Google Picture updated for user "@user".', ['@user' => $user->getEmail()]);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getGroupScopeForUser(AccountInterface $account, string $group_scope) {

    // Use cache static for reduce the query.
    $data = &drupal_static('getGroupScopeForUser', []);

    if (!isset($data[$account->id()][$group_scope])) {

      // Retrieve all groups for this scope.
      $group_scope_ids = $this->groupStorage->getQuery()
        ->condition('field_group_scope', $group_scope)
        ->sort('label')
        ->execute();

      // Retrieve the groups assign to user.
      $group_user_ids = $this->groupManager->getGroupIdsByUser($account);
      $group_scope_ids = is_array($group_scope_ids) ? $group_scope_ids : [];

      // Intersect result for found the groups scope assign to user.
      $group_scope_of_user = array_intersect($group_user_ids, $group_scope_ids);

      if (count($group_scope_of_user) == 1) {

        // If found one group, use this.
        $group_id = array_values($group_scope_of_user)[0];

        /** @var \Drupal\group\Entity\GroupInterface $group */
        $data[$account->id()][$group_scope] = $this->groupStorage->load($group_id);
      }
      else {

        // Search a correspondence in GroupsScope saved in self::updateData().
        $groups_scope = $this->userData->get('myaccess', $account->id(), 'groups_scope');
        $groups_scope = is_array($groups_scope) ? $groups_scope : [];
        $key = array_search($group_scope, array_column($groups_scope, 'scope'));

        if ($key !== FALSE && !empty($groups_scope[$key]['name'])) {
          $group_scope_name = $groups_scope[$key]['name'];

          // Search the group with this name.
          $group_scope_ids = $this->groupStorage->getQuery()
            ->condition('field_group_scope', $group_scope)
            ->condition('label', '%' . $group_scope_name . '%', 'LIKE')
            ->execute();

          if (!empty($group_scope_ids) && is_array($group_scope_ids)) {
            $group_id = array_values($group_scope_ids)[0];

            /** @var \Drupal\group\Entity\GroupInterface $group */
            $data[$account->id()][$group_scope] = $this->groupStorage->load($group_id);
          }
        }
        else {
          $data[$account->id()][$group_scope] = NULL;
        }
      }
    }

    return $data[$account->id()][$group_scope];
  }

  /**
   * Create needed groups.
   *
   * @param array $groups
   *   A list of groups.
   */
  private function ensureGroupsExist(array $groups) {
    foreach ($groups as $group) {
      try {
        $this->groupManager->createIfNotExists(
          $group['name'],
          $group['scope'],
          [GroupManagerInterface::CONTEXT_CONTENT]
        );
      }
      catch (GroupNotCreatedException $exception) {
        $this->logger->warning($exception->getMessage());
      }
    }
  }

}
