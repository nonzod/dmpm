<?php

namespace Drupal\myportal_staff_directory\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Url;

/**
 * Defines the Importer entity.
 *
 * @ConfigEntityType(
 *   id = "staff_member_importer",
 *   label = @Translation("Staff Member Importer"),
 *   handlers = {
 *     "list_builder" = "Drupal\myportal_staff_directory\ImporterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\myportal_staff_directory\Form\ImporterForm",
 *       "edit" = "Drupal\myportal_staff_directory\Form\ImporterForm",
 *       "delete" = "Drupal\myportal_staff_directory\Form\ImporterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "staff_member_importer",
 *   admin_permission = "administer site configuration",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/staff-directory/staff-member-importer/add",
 *     "edit-form" = "/admin/structure/staff-directory/staff-member-importer/{staff_member_importer}/edit",
 *     "delete-form" = "/admin/structure/staff-directory/staff-member-importer/{staff_member_importer}/delete",
 *     "collection" = "/admin/structure/staff-directory/staff-member-importer"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "url",
 *     "plugin",
 *     "auth_url",
 *     "retention_days"
 *   }
 * )
 */
class Importer extends ConfigEntityBase implements ImporterInterface {

  /**
   * The Importer ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Importer label.
   *
   * @var string
   */
  protected $label;

  /**
   * The URL from where the import file can be retrieved.
   *
   * @var string
   */
  protected $url;

    /**
   * The URL from where the token is retrieved.
   *
   * @var string
   */
  protected $auth_url;

  /**
   * The plugin ID of the plugin to be used for processing this import.
   *
   * @var string
   */
  protected $plugin;

  /**
   * Whether to update existing members if they have already been imported.
   *
   * @var int
   */
  protected $retention_days = 7;

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->url ? Url::fromUri($this->url) : NULL;
  }

    /**
   * {@inheritdoc}
   */
  public function getAuthUrl() {
    return $this->auth_url ? Url::fromUri($this->auth_url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetentionDays() {
    return $this->retention_days;
  }
}
