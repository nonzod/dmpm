<?php

namespace Drupal\myportal_staff_directory\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\myportal_staff_directory\Plugin\ImporterManager;

/**
 * Form for restoring backup.
 */
class BackupRestoreForm extends ContentEntityConfirmFormBase {

  private ImporterManager $importerManager;
  
  /**
   * BAckupRestoreForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MessengerInterface $messenger, ImporterManager $importerManager) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->time = $time;
    $this->messenger = $messenger;
    $this->importerManager = $importerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('myportal_staff_directory.staff_member_importer_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to restore backup %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.import_backup.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Restore');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin = $this->importerManager->createInstanceFromConfig('postman_mockup');
    
    if (is_null($plugin)) {
      $this->messenger->addMessage($this->t('The specified importer does not exist.'));
      return;
    }
    
    $result = $plugin->restoreBackup($this->entity);

    $message_values = ['@importer' => $plugin->getConfig()->label()];
    if ($result) {
      $this->messenger->addMessage($this->t('Backup @entity restored.', ['@entity' => $this->entity->label()]));

      return;
    }

    $this->messenger->addError($this->t('There was a problem running the "@importer" retore', ['@importer' => $message_values]));
  }
}
