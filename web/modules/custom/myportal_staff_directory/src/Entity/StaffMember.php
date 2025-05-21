<?php

namespace Drupal\myportal_staff_directory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the StaffMember entity.
 *
 * @ContentEntityType(
 *   id = "staff_member",
 *   label = @Translation("Staff Member"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\myportal_staff_directory\StaffMemberListBuilder",
 *     "form" = {
 *       "default" = "Drupal\myportal_staff_directory\Form\StaffMemberForm",
 *       "add" = "Drupal\myportal_staff_directory\Form\StaffMemberForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *      "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "staff_member",
 *   admin_permission = "Admin staff directory",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "smid",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/staff-directory/staff-member/{staff_member}",
 *     "add-form" = "/admin/structure/staff-directory/staff-member/add",
 *     "delete-form" = "/admin/structure/staff-directory/staff-member/{staff_member}/delete",
 *     "collection" = "/admin/structure/staff-directory/staff-member",
 *   }
 * )
 */
class StaffMember extends ContentEntityBase implements StaffMemberInterface {

  use EntityChangedTrait;

  private bool $html_reporting = FALSE;

  private string $raw_reporting = '';

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The member full name.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['position_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Position title'))
      ->setDescription(t('The member position title.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['directline_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Direct line'))
      ->setDescription(t('The member direct line number.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mobile_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Mobile number'))
      ->setDescription(t('The member mobile line number.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('E-Mail address'))
      ->setDescription(t('The member email address.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1
      ])
      ->setDisplayOptions('form', [
        'type' => 'email',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['function'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Function'))
      ->setDescription(t('The member function.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 10
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Country'))
      ->setDescription(t('The member country.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 90
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['legalentity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Legal entity'))
      ->setDescription(t('The member legal entity.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 91
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['global_employee_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Global Employee code'))
      ->setDescription(t('The member global employee code.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 92
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['reporting'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Superior E-Mail address'))
      ->setDescription(t('The superior email address.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1
      ])
      ->setDisplayOptions('form', [
        'type' => 'email',
        'weight' => -4,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['team'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Team E-Mail address'))
      ->setDescription(t('The team email addresses.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE);

    $fields['employee_scope'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Employee scope'))
      ->setDescription(t('The member employee scope (calculated).'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 92
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($first_name, $last_name): StaffMemberInterface {
    $this->set('name', "$first_name $last_name");

    return $this;
  }

  /**
   * 
   */
  public function getReportingEmail() {
    $reporting = $this->get('reporting')->getString();
    $ret = filter_var($reporting, FILTER_VALIDATE_EMAIL);
    if($ret === false) {
      $user = array_shift(\Drupal::entityTypeManager()->getStorage('staff_member')->loadByProperties(['global_employee_code' => $reporting]));

      if($user->get('email')->isEmpty()) {
        $ret = $reporting;
      } else {
        $ret = $user->get('email')->getString();
      }
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function setReportingToHtml(): StaffMemberInterface {
    // Check if reporting exists and is not already processed as HTML
    if ($this->get('reporting')->isEmpty() || $this->html_reporting)
      return $this;

    // Backup raw email address
    $this->html_reporting = TRUE;
    $this->raw_reporting = $this->getReportingEmail();

    $query = \Drupal::entityQuery('staff_member');
    $query->accessCheck(FALSE);
    $condition = $query->orConditionGroup()
      ->condition('email', $this->raw_reporting)
      ->condition('global_employee_code', $this->raw_reporting);

    $query->condition($condition);
    $smid = reset($query->execute());
    
    $leader = \Drupal::entityTypeManager()->getStorage('staff_member')->load($smid);

    if ($leader)
      $this->set('reporting', '<a class="member-details-ref" href="#" data-smid="' . $leader->get('smid')->getString() . '">' . $leader->get('name')->getString() . '</a>');

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReportingToText(): StaffMemberInterface {
    // Check if reporting exists and is already processed as HTML
    if ($this->get('reporting')->isEmpty() || !$this->html_reporting)
      return $this;

    // Restore raw email address
    $this->html_reporting = TRUE;
    $this->set('reporting', $this->raw_reporting);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTeamToHtml(): StaffMemberInterface {
    $teams = \Drupal::entityTypeManager()->getStorage('staff_member')->loadByProperties(['reporting' => $this->get('email')->getString()]);

    foreach ($teams as $smid => $team_member) {
      $name = $team_member->get('name')->getString();
      $smid = $team_member->get('smid')->getString();

      $this->get('team')->appendItem('<a class="member-details-ref" href="#" data-smid="' . $smid . '">' . $name . '</a>');
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEmployeeType(array $json_member): string {
    if ($json_member['Country'] == "Italy") {
      $type = "CORPORATE";
    } elseif (in_array($json_member['Legalentity'], self::getRegionalLegalentities())) {
      $type = "REGIONAL";
    } else {
      $type = "LOCAL";
    }

    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public static function getRegionalLegalentities(): array {
    return [
      'A. Menarini Asia-Pacific Holdings Pte. Ltd.',
      'A. Menarini Asia-Pacific Pte. Ltd'
    ];
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
  public function setCreatedTime($timestamp): StaffMemberInterface {
    $this->set('created', $timestamp);
    return $this;
  }
}
