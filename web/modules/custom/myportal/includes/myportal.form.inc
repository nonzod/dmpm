<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_alter().
 */
function myportal_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {

  $form['field_hide_author']['#group'] = 'author';
  $form['field_author']['#group'] = 'author';

  switch ($form_id) {
    case 'node_event_form':
      unset($form['field_event_managers']);
      if (isset($form['field_link_channel_streaming'])) {
        $form['field_link_channel_streaming']['#states'] = [
          'visible' => [
            ':input[name="field_menarini_channel_events[value]"]' => ['checked' => TRUE],
          ],
        ];
      }
      break;

    case 'tac_lite_user_access_form':
      $form['#attributes']['class'][] = 'card';
      break;

    case 'node_page_form':
    case 'node_page_edit_form':
      $form['actions']['submit']['#validate'][] = 'Drupal\myportal\Validate\ValidateCallback::validateForm';
      break;
  }
}
