<?php

namespace Drupal\myportal_localadmin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage Drupal Role for Local Admin users
 */
class LocalAdminDrupalRoleForm extends FormBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;


  /**
   * Allowed drupal roles for Local Admin
   * 
   * @var array
   */
  protected array $allowedRoles = ["hmrs_no_wepeople", "editor", "mylink_admin"];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $entity_type_manager = $container->get('entity_type.manager');
    $user_storage = $entity_type_manager->getStorage('user');
    $instance->userStorage = $user_storage;

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
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $available_roles = Role::loadMultiple($this->allowedRoles);
    $user_roles = $user->getRoles(TRUE);

    $options = [];
    foreach ($available_roles as $drupal_role) {
      $options[$drupal_role->id()] = $drupal_role->label();
    }

    $form['#title'] = $this->t('Drupal roles for :user', [':user' => $user->getDisplayName()]);

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('User roles'),
      '#required' => FALSE,
      '#options' => $options,
      '#default_value' => $user_roles
    ];

    $form_state->set('uid', $user->id());

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
    if (empty($uid = $form_state->get('uid'))) {
      return;
    }
    $account = $this->userStorage->load($uid);
    $roles_state = $form_state->getValue('roles');
    $res = null;

    foreach ($roles_state as $rk => $rv) {
      if ($rv == "0") {
        if($account->hasRole($rk)) {
          $account->removeRole($rk);
          $res = true;
        }
      } else {
        if(!$account->hasRole($rk)) {
          $account->addRole($rk);
          $res = true;
        }
      }
    }

    if($res !== null) {
      $account->save();
      \Drupal::messenger()->addMessage($this->t('Drupal roles for <strong>:user</strong> updated', [':user' => $account->getDisplayName()]));
    }
  }
}
