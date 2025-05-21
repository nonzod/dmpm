<?php

namespace Drupal\myportal_group\Plugin\Field\FieldWidget;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\myaccess\Entity\ApplicationInterface;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myportal_group\Access\MyPortalGroupAccessCheck;
use Drupal\social_group\Plugin\Field\FieldWidget\SocialGroupSelectorWidget;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * A widget to select a group when creating an entity in a group.
 *
 * @FieldWidget(
 *   id = "myportal_group_selector_widget",
 *   label = @Translation("Myportal group select list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class MyPortalGroupSelectorWidget extends SocialGroupSelectorWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $originalElement = parent::formElement($items, $delta, $element, $form, $form_state);
    $form_display = $form_state->get('form_display');
    if (!$form_display) {
      return $element;
    }
    $form_used = $form_display->id();

    $form['default_visibility']['#value'] = MyPortalGroupAccessCheck::CONTENT_VISIBILITY_GROUP;
    if (empty($form['field_content_visibility']['widget']['#default_value'])) {
      $form['field_content_visibility']['widget']['#default_value'] = MyPortalGroupAccessCheck::CONTENT_VISIBILITY_GROUP;
    }
    $form['#validate'][] = __CLASS__ . '::validateGroupElement';

    /*
     * @todo Replace attribute 'style' with CSS classes.
     */
    $element['container-selected-items'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'container-selected-items',
        'style' => 'margin-bottom: 1rem;',
      ],
    ];

    /*
     * Make the list unique because getSelectedOptions() returns duplicates on
     * translated contents.
     */
    $element['container-selected-items']['list'] = self::buildSelectedItemsList(
      array_unique($this->getSelectedOptions($items)));

    $element['container']['#type'] = 'container';

    // Relocate messages.
    $element['#suffix'] = '';
    $element['container']['#prefix'] = '<div id="group-selection-result"></div>';

    if (isset($originalElement['#options']) && is_array($originalElement['#options'])) {
      $index = 1;
      foreach ($originalElement['#options'] as $parentLabel => $childOptions) {
        $childOptions = $this->alterGroupsByContext($childOptions, $form_used);
        // If childOptions is empty don't show the reference accordion.
        if (empty($childOptions)) {
          continue;
        }

        $detailsIndex = 'details-' . $index;
        $checkboxesIndex = 'checkboxes-' . $index;
        $element['container'][$detailsIndex]['#type'] = 'details';
        $element['container'][$detailsIndex]['#title'] = $parentLabel;
        /*
         * @todo Replace attribute 'style' with CSS classes.
         */
        $element['container'][$detailsIndex]['#summary_attributes']['style'] =
          'box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);';
        $element['container'][$detailsIndex][$checkboxesIndex] = $originalElement;
        $element['container'][$detailsIndex][$checkboxesIndex]['#type'] = 'checkboxes';
        $element['container'][$detailsIndex][$checkboxesIndex]['#options'] = $childOptions;
        $element['container'][$detailsIndex][$checkboxesIndex]['#ajax']['callback'] = __CLASS__ . '::validateGroupSelection';
        $element['container'][$detailsIndex][$checkboxesIndex]['#multiple'] = TRUE;

        unset($element['container'][$detailsIndex][$checkboxesIndex]['#title']);

        /*
         * @todo
         * Why #default_value has all selected items of all checkboxes elements?
         * The checkboxes element has only its selected elements.
         * That is why we need to normalize the values.
         */
        $element['container'][$detailsIndex][$checkboxesIndex]['#default_value'] =
          $this->normalizeDefaultValues(
            $element['container'][$detailsIndex][$checkboxesIndex]['#default_value'],
            $element['container'][$detailsIndex][$checkboxesIndex]['#options']);

        $selectedCounter = count($element['container'][$detailsIndex][$checkboxesIndex]['#default_value']);
        $formattedCounter = new FormattableMarkup(
          '<span class="counter-wrapper">@counter</span>',
          ['@counter' => $selectedCounter]);
        $element['container'][$detailsIndex]['#description'] = $this->t('@counter Group selected', [
          '@counter' => $formattedCounter,
        ]);

        $index++;
      }
      $element['container']['#count'] = $index - 1;
    }

    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity): array {
    // Get the options from the parent method.
    $options = parent::getOptions($entity);

    /** @var \Drupal\user\Entity\User $account */
    $account = $this->userManager->load($this->currentUser->id());

    // Remove groups the user does not have create access to
    // Applied for only application and users that doesn't have role
    // "site-manager", "content-manager" or "administrator".
    if ($entity instanceof ApplicationInterface
      && empty(array_intersect([
        'administrator',
        'sitemanager',
        'contentmanager',
      ], $account->getRoles()))
    ) {
      $options = $this->removeGroupsWithoutCreateAccess($options, $account, $entity, [
        'flexible_group-editor',
        'flexible_group-content_editor',
        'flexible_group-group_admin',
        'flexible_group-group_manager',
      ]);
    }

    // Initialize arrays for grouping the options and saving their
    // original index.
    $groupedOptions = [];
    $optionIndex = [];

    // Iterate through each option and group it based on the entity
    // reference group.
    foreach ($options as $gid => $title) {
      // Load the group entity.
      if ($group = Group::load($gid)) {
        // Get the group scope field and its possible options.
        /** @var \Drupal\Core\Field\FieldItemListInterface $group_scope_field */
        $group_scope_field = $group->get('field_group_scope');
        $options_provider = $group_scope_field->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getOptionsProvider('value', $group_scope_field->getEntity());
        $possible_options = ($options_provider !== NULL) ? $options_provider->getPossibleOptions() : [];

        // Get the group scope value and label.
        $group_scope_value = $group_scope_field->getString();
        if (!empty($group_scope_value) && isset($possible_options[$group_scope_value])) {
          $group_scope_label = $possible_options[$group_scope_value];
        }
        else {

          // Custom assign label based by title of group.
          switch ($title) {
            case GroupManagerInterface::EXTERNAL:
            case GroupManagerInterface::INTERNAL:
              $group_scope_label = (string) $this->t('Employees type');
              break;

            case GroupManagerInterface::MANAGER:
            case GroupManagerInterface::NO_MANAGER:
              $group_scope_label = (string) $this->t('Manager type');
              break;

            default:
              $group_scope_label = (string) $this->t('Not assigned');
          }
        }

        // Save the option and its group scope label and index.
        $groupedOptions[$gid] = [
          'title' => $title,
          'groupScopeLabel' => $group_scope_label,
        ];

        // Save the original option index.
        $optionIndex[$gid] = array_search($group_scope_value, array_keys($possible_options));
      }
    }

    // Sort the option index by the original order.
    asort($optionIndex);

    // Assign the options to their corresponding groups, with the
    // "Employees type" and "Manager type" group listed first.
    $result = [];
    $result[(string) $this->t('Employees type')] = [];
    $result[(string) $this->t('Manager type')] = [];

    foreach ($optionIndex as $gid => $position) {
      $group_scope_label = $groupedOptions[$gid]['groupScopeLabel'];

      $result[$group_scope_label][$gid] = $groupedOptions[$gid]['title'];
    }

    return $result;
  }

  /**
   * Validate the group selection and change the visibility settings.
   *
   * @param array $form
   *   Form to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state to process.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response changing values of the visibility field and set status message.
   */
  public static function validateGroupSelection(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $form_id = $form['#form_id'];
    $selected_container = self::getSelectedGroups($form_state->getValue('groups'));
    if ('application_mylinks_add_form' === $form_id || 'application_mylinks_edit_form' === $form_id) {
      $selected_container = self::getSelectedGroups($form_state->getValue('field_application_access'));
    }

    $selectedGroups = array_column($selected_container, 'target_id');
    $renderer = \Drupal::service('renderer');
    $html_render = self::buildSelectedItemsList($selectedGroups);
    $html = $renderer->render($html_render);
    $ajax_response->addCommand(new HtmlCommand('#container-selected-items', $html));

    return $ajax_response;
  }

  /**
   * Validate Group & Visibility form element.
   *
   * @param array $form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateGroupElement(array $form, FormStateInterface $form_state) {
    $form_id = $form['#form_id'];
    if ('application_mylinks_add_form' === $form_id || 'application_mylinks_edit_form' === $form_id) {
      $validation = FALSE;
      foreach ($form_state->getValue('field_application_access')['container'] as $value) {
        $value = reset($value);
        if (!empty($value) && !$validation) {
          $validation = TRUE;
        }
      }

      if (!$validation) {
        $form_state->setErrorByName('field_application_access', t('At least one group is required.'));
      }
    }
    else {
      $groups = self::getSelectedGroups($form_state->getValue('groups'));
      // Remove duplicates in multi-dimensionals array.
      $selectedItems = array_map('unserialize', array_unique(array_map('serialize', $groups)));
      if (empty($selectedItems)) {
        $form_state->setErrorByName('group_group_visibility', t('At least one group is required.'));
      }
    }

  }

  /**
   * Gets selected groups.
   *
   * @param mixed $groups_value
   *   Field groups value.
   *
   * @return array
   *   Selected group ids.
   */
  public static function getSelectedGroups($groups_value): array {
    $groups = [];
    if ($groups_value !== NULL) {
      array_walk_recursive($groups_value, static function ($item, $key) use (&$groups) {
        if ($key === 'target_id') {
          $groups[] = [
            'target_id' => $item,
          ];
        }
      });
    }

    return $groups;
  }

  /**
   * Gets default groups.
   *
   * @param array $groups_value
   *   Field groups value.
   *
   * @return array
   *   Selected group ids.
   */
  public static function getDefaultGroups(array $groups_value): array {
    $groups = [];
    if (isset($groups_value['#count'])) {
      for ($i = 1; $i <= (int) $groups_value['#count']; $i++) {
        $detailsIndex = 'details-' . $i;
        $checkboxesIndex = 'checkboxes-' . $i;
        $groups[] = $groups_value[$detailsIndex][$checkboxesIndex]['#default_value'];
      }
    }

    return array_unique(call_user_func_array('array_merge', $groups));
  }

  /**
   * Removes form #default_value not available options.
   *
   * @param array $default_values
   *   Default values selected.
   * @param array $available_options
   *   Available options.
   *
   * @return array
   *   Normalized default values.
   */
  private function normalizeDefaultValues(array $default_values, array $available_options): array {
    $default_values = array_unique($default_values);
    $available_options = array_keys($available_options);

    /*
     * @todo Set default values.
     * Default value has to be one of user's groups.
     */
    return array_filter($default_values, function ($item) use ($available_options) {
      return in_array($item, $available_options);
    });
  }

  /**
   * Build selected groups list.
   *
   * @param array $selected_options
   *   List of selected group ids.
   *
   * @return array
   *   List renderable array.
   *
   * @todo Replace attribute 'style' with CSS classes.
   */
  private static function buildSelectedItemsList(array $selected_options): array {
    $itemsList = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [],
      '#attributes' => [
        'style' => 'margin: 0',
        'class' => ['form-actions'],
      ],
      '#wrapper_attributes' => [
        'class' => 'container-selected-items--list',
      ],
    ];

    foreach ($selected_options as $groupId) {
      $group = Group::load($groupId);
      if ($group !== NULL) {
        $itemsList['#items'][] = [
          '#wrapper_attributes' => [
            'data-group-id' => $groupId,
            'style' => 'border-radius: 16px; background: #D3D4D9; color: #545560; padding: 0 1rem; margin-bottom: 0; margin-left: 0;',
            'class' => [
              'action-link',
              'action-link--extrasmall',
              'action-link--icon-ex',
              'group-item--selected',
            ],
          ],
          '#markup' => $group->label(),
        ];
      }
    }

    usort($itemsList['#items'], function ($prev, $next) {
      return strcmp($prev['#markup'], $next['#markup']);
    });

    return $itemsList;
  }

  /**
   * Removes groups that do not belong to the correct context.
   *
   * @param array $groups
   *   An array of Group entities keyed by their IDs.
   * @param string $form_used
   *   Form that the widget is using, e.g. "node.page.default".
   *
   * @return string[]
   *   Returns the array of groups filtered by context.
   */
  private function alterGroupsByContext(array $groups, string $form_used): array {
    // Determine the context based on the form used.
    switch ($form_used) {
      case 'node.page.default':
      case 'node.topic.default':
      case 'node.event.default':
        $context = GroupManagerInterface::CONTEXT_CONTENT;

        break;

      case 'application.mylinks.default':
        $context = GroupManagerInterface::CONTEXT_MYLINKS;

        break;

      default:
        // If the form used is not recognized, return an empty array.
        return [];
    }

    // Load only the required groups using a custom query.
    $group_ids = array_keys($groups);

    if (empty($group_ids)) {
      return [];
    }

    $group_query = $this->entityTypeManager
      ->getStorage('group')
      ->getQuery()
      ->condition('id', $group_ids, 'IN')
      ->condition('field_group_context', $context);

    $group_ids = $group_query->execute();
    $groups = Group::loadMultiple($group_ids);

    // Map the getGroupLabel function over the loaded groups to get their
    // labels.
    return array_map([$this, 'getGroupLabel'], $groups);
  }

  /**
   * Returns the label of a Group entity as a string.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity.
   *
   * @return string
   *   The label of the Group entity as a string.
   *
   * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
   */
  private function getGroupLabel(GroupInterface $group): string {
    return (string) $group->label();
  }

  /**
   * Remove options from the list.
   *
   * @param array $options
   *   A list of options to check.
   * @param \Drupal\user\Entity\User $account
   *   The user to check for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for.
   * @param array $roles
   *   The roles target.
   *
   * @return array
   *   An list of options for the field containing groups with create access.
   */
  private function removeGroupsWithoutCreateAccess(array $options, User $account, EntityInterface $entity, array $roles) {

    foreach ($options as $option_category_key => $groups_in_category) {
      if (is_array($groups_in_category)) {
        foreach ($groups_in_category as $gid => $group_title) {
          if (!$this->checkGroupRoleAccess($gid, $account, $entity, $roles)) {
            unset($options[$option_category_key][$gid]);
          }
        }
        // Remove the entire category if there are no groups for this author.
        if (empty($options[$option_category_key])) {
          unset($options[$option_category_key]);
        }
      }
      else {
        if (!$this->checkGroupRoleAccess($option_category_key, $account, $entity, $roles)) {
          unset($options[$option_category_key]);
        }
      }
    }

    return $options;
  }

  /**
   * Check if user has role in group.
   *
   * @param int $gid
   *   Group id.
   * @param \Drupal\user\Entity\User $account
   *   The user to check for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node bundle to check for.
   * @param array $roles
   *   The roles target.
   *
   * @return int
   *   Either TRUE or FALSE.
   */
  private function checkGroupRoleAccess($gid, User $account, EntityInterface $entity, array $roles) {

    // Retrieve the group.
    $group = Group::load($gid);

    // Retrieve the membership of user for this group.
    $group_membership = $group->getMember($account);
    if (!$group_membership instanceof GroupMembership) {
      return FALSE;
    }

    // Retrieve the roles in group.
    $group_roles = $group_membership->getRoles();
    foreach ($group_roles as $group_role) {
      if (in_array($group_role->id(), $roles)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
