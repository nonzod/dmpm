<?php

namespace Drupal\myportal_staff_directory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Backup entity.
 *
 * @ContentEntityType(
 *   id = "import_backup",
 *   label = @Translation("Import backup files"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\myportal_staff_directory\BackupListBuilder",
 *     "form" = {
 *       "restore" = "Drupal\myportal_staff_directory\Form\BackupRestoreForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *      "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "staff_member_backup",
 *   admin_permission = "administer site configuration",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/staff-directory/staff-member-backup",
 *     "canonical" = "/admin/structure/staff-directory/staff-member-backup/{import_backup}",
 *     "restore-form" = "/admin/structure/staff-directory/staff-member-backup/{import_backup}/restore",
 *     "delete-form" = "/admin/structure/staff-directory/staff-member-backup/{import_backup}/delete",
 *   }
 * )
 */
class Backup extends ContentEntityBase implements BackupInterface {

  use EntityChangedTrait;

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The backup name.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDescription(t('The backup file.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'file',
        'weight' => 10
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['importer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Importer'))
      ->setDescription(t('The importer used.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'd/m/Y H:s',
        ],
        'weight' => 90,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp): BackupInterface {
    $this->set('created', $timestamp);
    return $this;
  }
}
