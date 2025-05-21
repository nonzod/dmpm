<?php

declare(strict_types=1);

namespace Drupal\myaccess\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Defines the Application entity.
 *
 * @ingroup advertiser
 *
 * @ContentEntityType(
 *   id = "application",
 *   label = @Translation("Application"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\myaccess\Entity\View\ApplicationViewsData",
 *     "access" = "Drupal\myaccess\Entity\Access\ApplicationAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\myaccess\Form\ApplicationForm",
 *       "default" = "Drupal\myaccess\Form\ApplicationForm",
 *       "delete" = "Drupal\myaccess\Form\ApplicationDeleteForm",
 *       "edit" = "Drupal\myaccess\Form\ApplicationForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "application",
 *   data_table = "application_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "bundle" = "bundle",
 *     "published" = "status",
 *   },
 *   admin_permission = "administer site configuration",
 *   bundle_entity_type = "application_type",
 *   field_ui_base_route = "entity.application_type.edit_form",
 *   links = {
 *     "canonical" = "/application/{application}",
 *     "collection" = "/admin/content/mylinks-overview"
 *   }
 * )
 */
class Application extends ContentEntityBase implements ApplicationInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  // Application defined into the Resource Admin and keep synchronized with it.
  const REMOTE = 'remote';

  // Application created and managed inside MyPortal.
  const LOCAL = 'local';

  // Google applications.
  const GOOGLE = 'google';

  // MyLinks applications.
  const MYLINKS = 'mylinks';

  /**
   * {@inheritDoc}
   */
  public function hasFavorite(): bool {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getType(): string {
    return $this->bundle();
  }

  /**
   * {@inheritDoc}
   */
  public function getSettings(): array {
    if($this->{'settings'}->value){
      $settings = unserialize($this->{'settings'}->value);
      return $settings;
    }
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getUrl(): string {
    switch ($this->getType()) {
      case Application::REMOTE:
        $url = Url::fromRoute(
          'myaccess.open',
          ['application' => $this->id()]
        )->toString();
        assert(is_string($url));
        return $url;

      default:
        return $this->url->getString();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getImageUrl() {
    switch ($this->getType()) {
      case Application::MYLINKS:
        if ($this->hasField('field_application_icon')) {
          $application_icon = $this->get('field_application_icon')->getValue();
          if ($application_icon !== []) {
            $fid = reset($application_icon);
            /** @var \Drupal\file\Entity\File $file */
            $file = File::load($fid['target_id']);

            return $file->createFileUrl(FALSE) ?? '';
          }

          return '';

        }

        return '';

      default:
        return $this->imageUrl->value;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription(): string {
    if ($this->hasField('description')) {
      return $this->get('description')->getString();
    }

    return '';
  }

  /**
   * {@inheritDoc}
   */
  public function getGroups(): array {
    if ($this->hasField('field_application_access')) {
      $groups = $this->get('field_application_access')->getValue();

      return array_column($groups, 'target_id');
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getVisibility(): string {
    switch ($this->getType()) {
      case Application::REMOTE:
        $settings = $this->getSettings();

        return isset($settings['visibility'])
        && is_string($settings['visibility']) ? trim(strtolower($settings['visibility'])) : 'private';

      default:
        return 'public';
    }
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('The title of the Application entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('The description of the Application entity.'))
      ->setReadOnly(TRUE);

    $fields['imageUrl'] = BaseFieldDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('Image Url'))
      ->setDescription(new TranslatableMarkup('The imageUrl of the Application entity.'))
      ->setReadOnly(TRUE);

    $fields['url'] = BaseFieldDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('Url'))
      ->setDescription(new TranslatableMarkup('The url of the Application entity.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setReadOnly(TRUE);

    $fields['categories'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Categories'))
      ->setDescription(new TranslatableMarkup('The categories of the Application entity.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setReadOnly(TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Remote ID'))
      ->setDescription(new TranslatableMarkup('The remote ID of the Application entity.'))
      ->setReadOnly(TRUE);

    $fields['settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Settings'))
      ->setDescription(new TranslatableMarkup('Specific settings for this application type.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Bundle'))
      ->setReadOnly(TRUE)
      ->setTargetBundle('default')
      ->setDescription(new TranslatableMarkup('The bundle of the Application entity.'));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Weight'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
