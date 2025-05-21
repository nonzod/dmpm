<?php

namespace Drupal\myportal_localadmin\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupRole;
use Drupal\myportal_localadmin\Manager\LocalAdminGroupRoleManager;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage Group Role for Local Admin users
 */
class LocalAdminGroupRoleForm extends FormBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\myportal_localadmin\Manager\LocalAdminGroupRoleManager
   */
  protected LocalAdminGroupRoleManager $roleManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->roleManager = $container->get('myportal_localadmin.group_role_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'local_admin_people_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupContentInterface $member = NULL, Group $group = NULL) {
    $this->roleManager->setMember($member);
    $this->roleManager->setGroup($group);

    $available_roles = GroupRole::loadMultiple($this->roleManager->getAllowedRoles());
    $user_roles = $this->roleManager->getGroupRoles();
    $user_name = $member->get('label')->getString();
    $group_name = $group->get('label')->getString();

    $options = [];
    foreach ($available_roles as $group_role) {
      $options[$group_role->id()] = $group_role->label();
    }

    $form['#title'] = $this->t('Group roles for :user', [':user' => $member->get('label')->getString()]);

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Members group roles'),
      '#required' => FALSE,
      '#options' => $options,
      '#default_value' => $user_roles
    ];

    $form_state->set('group_name', $group_name);
    $form_state->set('user_name', $user_name);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles_state = $form_state->getValue('roles');
    $res = null;

    foreach ($roles_state as $rk => $rv) {
      if ($rv == "0") {
        if ($this->roleManager->hasGroupRole($rk)) {
          $res = $this->roleManager->revokeGroupRole($rk);
        }
      } else {
        if (!$this->roleManager->hasGroupRole($rk)) {
          $res = $this->roleManager->grantGroupRole($rk);
        }
      }
    }

    if ($res !== null) {
      \Drupal::messenger()->addMessage($this->t('Group roles for <strong>:user</strong> in <strong>:group</strong> updated', [':user' => $form_state->get('user_name'), ':group' => $form_state->get('group_name')]));
      // Clear entity cache for group roles
      \Drupal::cache('entity')->deleteAll();
    }
  }
}
