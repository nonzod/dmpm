<?php

declare(strict_types=1);

namespace Drupal\myaccess\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Application type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "application_type",
 *   label = @Translation("Application type"),
 *   label_collection = @Translation("Application types"),
 *   label_singular = @Translation("application type"),
 *   label_plural = @Translation("application types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content type",
 *     plural = "@count content types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\BundleEntityFormBase",
 *       "edit" = "Drupal\Core\Entity\BundleEntityFormBase",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\myaccess\Entity\Handler\ApplicationTypeListBuilder",
 *   },
 *   admin_permission = "administer application types",
 *   config_prefix = "type",
 *   bundle_of = "application",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/applications/manage/{application_type}",
 *     "delete-form" = "/admin/structure/applications/manage/{application_type}/delete",
 *     "collection" = "/admin/structure/applications",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *   }
 * )
 */
class ApplicationType extends ConfigEntityBundleBase {

  /**
   * The machine name of this application type.
   *
   * @var string
   */
  protected $type;

  /**
   * The human-readable name of the application type.
   *
   * @var string
   */
  protected $name;

  /**
   * A brief description of this application type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

}
