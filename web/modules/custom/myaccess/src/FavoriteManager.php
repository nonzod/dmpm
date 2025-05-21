<?php

declare(strict_types=1);

namespace Drupal\myaccess;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\myaccess\Entity\Application;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Drupal\flag\FlagCountManagerInterface;

/**
 * Class FavoriteManager, used to remove zombie applications from favorites.
 */
class FavoriteManager implements FavoriteManagerInterface {

  const FAVORITE_APPLICATION = 'favorite_application';

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  private $flagService;

  /**
   * The ApplicationManager service.
   *
   * @var \Drupal\myaccess\ApplicationsManagerInterface
   */
  private $applicationManager;

  /**
   * The flag count service.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  private $flagCount;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * FavoriteManager constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The Flag Service.
   * @param \Drupal\myaccess\ApplicationsManagerInterface $application_manager
   *   The Application Manager service.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count
   *   The flag count manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    LoggerInterface $logger,
    FlagServiceInterface $flag,
    ApplicationsManagerInterface $application_manager,
    FlagCountManagerInterface $flag_count,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user
  ) {
    $this->logger = $logger;
    $this->flagService = $flag;
    $this->applicationManager = $application_manager;
    $this->flagCount = $flag_count;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function removeZombieApplications(UserInterface $user, array $applications): void {
    // I check if the user has favorite applications.
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->flagService->getFlagById(FavoriteManager::FAVORITE_APPLICATION);
    $flag_user = $this->flagCount->getUserFlagFlaggingCount($flag, $user);
    $app_remote = [];
    if ($flag_user === 0) {
      return;
    }

    foreach ($applications as $application) {
      $app_remote[] = $application->id();
    }

    $app_local = $this->applicationManager->getMyLinksApplications($user);

    try {
      $flagging_storage = $this->entityTypeManager->getStorage('flagging');

      $query = $flagging_storage->getQuery();
      $query->condition('flag_id', FavoriteManager::FAVORITE_APPLICATION);
      $query->condition('uid', $user->id());
      $ids = $query->execute();
      assert(is_array($ids));

      /** @var \Drupal\flag\Entity\Flagging[] $flaggings */
      $flaggings = $flagging_storage->loadMultiple($ids);

      foreach ($flaggings as $flagging) {
        $app_id = (int) $flagging->entity_id->value;

        /** @var \Drupal\myaccess\Entity\Application $app */
        $app = $this->entityTypeManager
          ->getStorage('application')->load($app_id);
        $bundle = $app->bundle();

        if ($bundle == Application::GOOGLE || $bundle == Application::LOCAL) {
          continue;
        }

        if (!empty($app_local) && $bundle == Application::MYLINKS) {
          $this->removeApplication($app_local, $app_id);
        }
        else {
          $this->removeApplication($app_remote, $app_id);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('FavoriteManager throw exception in "removeZombieApplications" with user "@user": @message.', [
        '@user' => $user->getAccountName(),
        '@message' => $e->getMessage(),
      ]);

      return;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function removeApplication(array $applications, int $app_id) {
    $app_remove = [];

    if (!in_array($app_id, $applications)) {
      $app_remove[] = $app_id;
    }

    if (!empty($app_remove)) {
      foreach ($app_remove as $application) {
        $application_id = $application;
        $this->unflagApplication($application_id);
      }
    }
  }

  /**
   * Removes the application from favorites.
   *
   * @param int $app_id
   *   Application id.
   */
  private function unflagApplication(int $app_id) {
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->flagService->getFlagById(FavoriteManager::FAVORITE_APPLICATION);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->flagService->getFlaggableById($flag, $app_id);

    try {
      $this->flagService->unflag($flag, $entity);
      $this->logger
        ->debug("Remove Zombie Applications: unflag \"%app_label\".", ['%app_label' => $entity->label()]);
    }
    catch (\LogicException $e) {
      $this->logger->error('FavoriteManager throw exception in "unflagApplication" with app ID "@app_id": @message.', [
        '@app_id' => $app_id,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
