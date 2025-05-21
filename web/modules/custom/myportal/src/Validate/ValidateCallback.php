<?php

namespace Drupal\myportal\Validate;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class ValidateCallback, used for validate field field_navigation_section.
 */
class ValidateCallback {

  /**
   * Check how many elements of the field, navigation section, are selected.
   *
   * @param array $form
   *   Form to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('entity_validated', TRUE);
    $navigation_section = $form_state->getValue('field_navigation_section');
    $groups = $form_state->getValue('groups');
    $validation = self::validateGroups($groups);

    if (empty($navigation_section)) {
      $form_state->setErrorByName('field_navigation_section', t('Attention you must select at least 2 items for the Navigation Section'));
    }

    if (!empty($navigation_section)) {
      $selected_items = array_column($navigation_section, 'target_id');
      if (count($selected_items) < 2) {
        $form_state->setErrorByName('field_navigation_section', t('Attention you must select at least 2 items for the Navigation Section'));
      }
    }

    if (!$validation) {
      $form_state->setErrorByName('groups', t('At least one group is required.'));
    }

  }

  /**
   * Check if any groups have been associated with the content.
   *
   * @param array $groups
   *   Array with the elements of the groups.
   *
   * @return bool
   *   Return FALSE if not select groups in page.
   */
  private static function validateGroups(array $groups): bool {
    $validation = FALSE;
    if (!empty($groups) && !empty($groups['container'])) {
      foreach ($groups['container'] as $value) {
        $value = reset($value);
        if (!empty($value) && !$validation) {
          $validation = TRUE;

          continue;
        }
      }
    }

    return $validation;
  }

}
