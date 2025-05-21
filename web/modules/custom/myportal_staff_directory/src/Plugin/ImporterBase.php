<?php

namespace Drupal\myportal_staff_directory\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\myportal_staff_directory\Entity\ImporterInterface;
use Drupal\myportal_staff_directory\Entity\StaffMember;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Base class for Importer plugins.
 */
abstract class ImporterBase extends PluginBase implements ImporterPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Provides helpers to operate on files and stream wrappers.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  protected $baseuri = "private://staff_directory_backup/";

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager, ClientInterface $httpClient, FileSystemInterface $fileSystem, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->httpClient = $httpClient;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $config_factory;

    if (!isset($configuration['config'])) {
      $message = 'Missing Importer configuration.';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }

    if (!$configuration['config'] instanceof ImporterInterface) {
      $message = 'Wrong Importer configuration.';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return $this->configuration['config'];
  }

  /**
   * Save member entities imported
   */
  public function saveEntities(array &$data) {
    $storage = $this->entityTypeManager->getStorage('staff_member');

    foreach ($data as $member_data) {
      try {
        $member = $storage->create([]);

        $member->set('global_employee_code', $member_data['Globalempcode']);
        $member->set('name', "{$member_data['Name']} {$member_data['Surname']}");
        $member->set('position_title', $member_data['Title']);
        $member->set('directline_number', $member_data['Direct_Line']);
        $member->set('mobile_number', $member_data['Mobile']);
        $member->set('email', $member_data['Email']);
        $member->set('function', $member_data['Function']);
        $member->set('country', $member_data['Country']);
        $member->set('legalentity', $member_data['Legalentity']);
        $member->set('reporting', $member_data['Superior']);
        $member->set('legalentity', $member_data['Legalentity']);
        $member->set('employee_scope', StaffMember::getEmployeeType($member_data));

        $member->save();
      } catch (\Exception $e) {
        return FALSE;
      }
    }

    return count($data);
  }

  /**
   * Delete the last imported members in batches.
   */
  public function resetData() {
    $storage = \Drupal::entityTypeManager()->getStorage('staff_member');
    $query = $storage->getQuery()->accessCheck(FALSE);
    $ids = $query->execute();

    if (!empty($ids)) {
      do {
        $batch = array_splice($ids, 0, 50);
        if (!empty($batch)) {
          $entities = $storage->loadMultiple($batch);
          $storage->delete($entities);
          $storage->resetCache($batch);
        }
      } while (!empty($batch));
    }
  }

  /**
   * Create backup entity and file
   */
  public function createBackup(&$data, $type = 'json') {
    $config = $this->configuration['config'];

    // Check filesystem permissions
    if (!$this->fileSystem->prepareDirectory($this->baseuri, FileSystemInterface::CREATE_DIRECTORY)) {
      $message = 'Cannot write backup, check filesystem permissions.';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }

    // Create backup file
    $entity = \Drupal::entityTypeManager()->getStorage('import_backup')->create();
    $file_prefix = date("YmdHis");
    $file_name = "staff_members-$file_prefix.$type";

    try {
      /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
      $fileRepository = \Drupal::service('file.repository');
      $file = $fileRepository->writeData($data, "{$this->baseuri}$file_name", FileSystemInterface::EXISTS_REPLACE);

      $entity->set('file', $file->id());
      $entity->set('name', "Backup $file_prefix");
      $entity->set('importer', $config->id());
      $entity->save();

      $this->removeExpiredBackups();
    } catch (\Exception $e) {
      $message = 'Cannot create backup entity';
      $this->sendErrorNotification($message);
      throw new PluginException('Cannot create backup entity');
    }
  }

  /**
   * Restore the last backup
   * 
   */
  public function restoreLastBackup() {
    $backup_storage = \Drupal::entityTypeManager()->getStorage("import_backup");
    $query = \Drupal::entityQuery('import_backup');
    $query->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 1);

    $id = reset($query->execute());

    if (!$id) {
      $message = 'Unable to restore, cannot find last backup';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }

    return $this->restoreBackup($backup_storage->load($id));
  }

  /**
   * Delete Backup entity and File
   */
  public function deleteBackup($id) {
    $backup_storage = \Drupal::entityTypeManager()->getStorage("import_backup");
    $b = $backup_storage->load($id);
    $f = $b->get('file')->entity;
    $b->delete();
    if ($f)
      $f->delete();
  }

  /**
   * Delete backups over retention
   */
  public function removeExpiredBackups() {
    $config = $this->configuration['config'];
    $days = $config->get('retention_days');

    $query = \Drupal::entityQuery('import_backup');
    $query->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range($days, 100);
    $ids = $query->execute();

    foreach ($ids as $id) {
      $this->deleteBackup($id);
    }
  }

  public function sendErrorNotification(string $text) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $to = \Drupal::service('config.factory')->get('myportal_staff_directory.settings')->get('recipients');

    /*\Drupal::logger('social_xoverride_email_blocker')
    ->notice("Blocked mail for %to: %message.", [
      '%to' => '',
      '%message' => '',
    ]);
*/
    return $mailManager->mail('myportal_staff_directory', 'importer_error', $to, 'en', ['message' => $text], TRUE);
  }
}
