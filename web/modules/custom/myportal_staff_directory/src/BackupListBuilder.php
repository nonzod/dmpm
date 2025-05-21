<?php

namespace Drupal\myportal_staff_directory;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * EntityListBuilderInterface implementation responsible for the Backup entities.
 */
class BackupListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('FileID');
    $header['name'] = $this->t('Name');
    $header['importer'] = $this->t('Importer');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\myportal_staff_directory\Entity\Backup */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink();
    $row['importer'] = $entity->get('importer')->value;
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'custom', 'd-m-Y H:i');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Add Restore link to available operations
    $operations['restore'] = [
      'title' => $this->t('Restore'),
      'url' => Url::fromRoute('myportal_staff_directory.backup_restore', [
        'import_backup' => $entity->id(),
      ]),
      'weight' => 99
    ];

    return $operations;
  }
}
