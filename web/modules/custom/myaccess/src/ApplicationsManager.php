<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\myaccess\Controller\ApplicationController;
use Drupal\myaccess\Entity\Application;
use Drupal\myaccess\Entity\ApplicationInterface;
use Drupal\myportal_group\Access\MyPortalGroupAccessCheck;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines the ApplicationsManager class.
 *
 * @package Drupal\myaccess
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplicationsManager implements ApplicationsManagerInterface {

  use FunctionalTrait;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The Group manager service.
   *
   * @var \Drupal\myaccess\GroupManagerInterface
   */
  protected GroupManagerInterface $groupManager;

  /**
   * Application storage service.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected ContentEntityStorageInterface $applicationStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * ApplicationsManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param GroupManagerInterface $groupManager
   *   The Group manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    GroupManagerInterface $groupManager,
    AccountProxyInterface $current_user,
    UserDataInterface $user_data
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->groupManager = $groupManager;

    $application_storage = $entity_type_manager->getStorage('application');
    assert($application_storage instanceof ContentEntityStorageInterface);
    $this->applicationStorage = $application_storage;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
  }

  /**
   * {@inheritDoc}
   */
  public function saveOrUpdate(array $externalApplications): array {
    $applications = [];

    try {
      foreach ($externalApplications as $externalApplication) {
        $entities = $this->applicationStorage->loadByProperties(['remote_id' => $externalApplication->getId()]);

        if (empty($entities)) {
          /** @var \Drupal\myaccess\Entity\Application $application */
          $application = Application::create([
            'title' => $externalApplication->getDisplayName(),
            'description' => $externalApplication->getDescription(),
            'imageUrl' => $externalApplication->getImageUrl(),
            'url' => $externalApplication->getUrl(),
            'categories' => [],
            'remote_id' => $externalApplication->getId(),
            'settings' => $externalApplication->getSettings(),
            'status' => 1,
            'bundle' => Application::REMOTE,
          ]);
          $application->save();
        }
        else {
          /** @var \Drupal\myaccess\Entity\Application $application */
          $application = reset($entities);

          $application->set('title', $externalApplication->getDisplayName());
          $application->set('description', $externalApplication->getDescription());
          $application->set('imageUrl', $externalApplication->getImageUrl());
          $application->set('url', $externalApplication->getUrl());
          $application->set('categories', []);
          $application->set('remote_id', (string) $externalApplication->getId());
          $application->set('settings', $externalApplication->getSettings());
          $application->set('bundle', Application::REMOTE);

          if ($this->isApplicationChanged($application, [
            'title',
            'description',
            'type',
            'imageUrl',
            'url',
            'categories',
            'remote_id',
            'settings',
            'bundle',
          ])) {
            $application->save();
          }
        }

        $applications[] = $application;
      }

      return $applications;
    }
    catch (\Throwable $e) {
      $this->logger->error('ApplicationsManager service in "saveOrUpdate" method throw exception: @message.', ['@message' => $e->getMessage()]);

      return [];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getUserApplications(UserInterface $user): array {
    $field_applications = $user->get('field_applications');
    assert($field_applications instanceof EntityReferenceFieldItemListInterface);

    $apps = $field_applications->referencedEntities();

    usort($apps,
      function (EntityInterface $app1, EntityInterface $app2) {
        return strtolower($app1->label() ?? '') <=> strtolower($app2->label() ?? '');
      }
    );

    return $this->map($apps, function (EntityInterface $app): int {
      return intval($app->id());
    });
  }

  /**
   * {@inheritDoc}
   */
  public function getLocalApplications(): array {
    $storage = $this->entityTypeManager->getStorage('application');

    return array_map(function (EntityInterface $app): int {
      return intval($app->id());
    }, $storage->loadByProperties(['bundle' => Application::LOCAL]));
  }

  /**
   * {@inheritDoc}
   */
  public function getGoogleApplications(): array {
    return array_map(function (EntityInterface $app): int {
      return intval($app->id());
    }, $this->applicationStorage->loadByProperties(['bundle' => Application::GOOGLE]));
  }

  /**
   * {@inheritDoc}
   */
  public function toSearchArray($application_id): array {
    /** @var \Drupal\myaccess\Entity\Application $application */
    $application = $this->applicationStorage->load($application_id);

    if ($application == NULL) {
      return [];
    }

    return [
      'id' => $application->id(),
      'title' => $application->label(),
      'description' => $application->{'description'}->value,
    ];
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function getMyLinksApplications(UserInterface $user): array {
    // Get groups for current users.
    $groups_id = $this->groupManager->getGroupIdsByUser($user);
    $mylinks = [];

    if (empty($groups_id)) {
      return [];
    }

    try {
      $query = $this->applicationStorage
        ->getQuery()
        ->condition('status', 1)
        ->condition('bundle', Application::MYLINKS)
        ->sort('weight', 'DESC');

      $results = $query->execute();

      if (!empty($results)) {
        // Ensure that $results is an array.
        if (!is_array($results)) {
          $results = [$results];
        }

        /** @var \Drupal\myaccess\Entity\Application[] $applications */
        $applications = $this->applicationStorage->loadMultiple($results);

        foreach ($applications as $application) {
          if ($application->hasField('field_application_access')) {
            // An array of group's ids this application is in.
            $groups_application = $application->getGroups();

            if (
              $user->hasPermission('administer application types') ||
              (!empty($groups_application) && $this->hasSameGroups($groups_id, $groups_application, $application))
            ) {
              $mylinks[] = $application->id();
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('ApplicationsManager service in "getMyLinksApplications" method throw exception: @message.', ['@message' => $e->getMessage()]);
    }

    return array_map('intval', $mylinks);
  }

  /**
   * Check if the application is changed.
   *
   * @param \Drupal\myaccess\Entity\ApplicationInterface $application
   *   The application object.
   * @param array $fields
   *   The array fields to check.
   *
   * @return bool
   *   True if fields are changed.
   */
  protected function isApplicationChanged(ApplicationInterface $application, array $fields = []): bool {
    $original_application = $this->applicationStorage->loadUnchanged($application->id());
    if (!$original_application) {
      return TRUE;
    }
    $application_fields = array_intersect_key($application->toArray(), array_flip($fields));
    $original_application_fields = array_intersect_key($original_application->toArray(), array_flip($fields));

    return $application_fields !== $original_application_fields;
  }

  /**
   * Checks if application and member belong to the same groups.
   *
   * @param array $user_groups
   *   User groups.
   * @param array $application_groups
   *   Applications groups.
   * @param \Drupal\myaccess\Entity\ApplicationInterface $application
   *   The application entity.
   *
   * @return bool
   *   TRUE if application and member belong to the same groups.
   */
  private function hasSameGroups(array $user_groups, array $application_groups, ApplicationInterface $application): bool {
    // Since the field_application_visibility field did not exist when the
    // Application entity was created, thereforethere are entities that were
    // already created without this field, and in this case, for these
    // entities, the value of the field will always be empty, since it was not
    // saved in the database, that is why for such entities, to maintain
    // correct behavior, we take group-all visibility by default.
    $visibility = $application->get('field_application_visibility')->getString() ?: MyPortalGroupAccessCheck::CONTENT_VISIBILITY_GROUP_ALL;

    switch ($visibility) {
      case MyPortalGroupAccessCheck::CONTENT_VISIBILITY_GROUP_ALL:
        return empty(array_diff($application_groups, $user_groups));

      case MyPortalGroupAccessCheck::CONTENT_VISIBILITY_GROUP:
        return !empty(array_intersect($user_groups, $application_groups));

      default:
        return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFavoriteApplicationIds(): array {
    $flagging_storage = $this->entityTypeManager->getStorage('flagging');

    $query = $flagging_storage->getQuery();
    $query->condition('flag_id', 'favorite_application');
    $query->condition('uid', $this->currentUser->id());
    $ids = $query->execute();
    assert(is_array($ids));

    /** @var \Drupal\flag\Entity\Flagging[] $flaggings */
    $flaggings = $flagging_storage->loadMultiple($ids);

    $applications = [];
    foreach ($flaggings as $flagging) {
      $applications[] = $flagging->entity_id->value;
    }

    return array_slice($applications, 0, ApplicationController::MAX_FAVORITE_APPLICATIONS_NUMBER);
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedFavoriteApplicationIds(): array {
    return $this->userData->get('myaccess', $this->currentUser->id(), 'application_ids') ?? $this->getFavoriteApplicationIds();
  }

  /**
   * {@inheritdoc}
   */
  public function setSortedFavoriteApplicationIds(array $sorted_application_ids): void {
    $this->userData->set('myaccess', $this->currentUser->id(), 'application_ids', $sorted_application_ids);
  }

}
