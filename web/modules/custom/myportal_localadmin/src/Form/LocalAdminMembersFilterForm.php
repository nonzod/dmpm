<?php

namespace Drupal\myportal_localadmin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Search member by name in user list
 */
class LocalAdminMembersFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "local_admin_members_filter_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#method'] = 'get';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
