<?php

namespace Drupal\myportal_localadmin\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 
 */
class LocalAdminNavigationSectionsForm extends FormBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $entity_type_manager = $container->get('entity_type.manager');
    $user_storage = $entity_type_manager->getStorage('user');
    $instance->userStorage = $user_storage;
    $term_storage = $entity_type_manager->getStorage('taxonomy_term');
    $instance->termStorage = $term_storage;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'local_admin_navigation_sections';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $terms = $this->termStorage->loadTree('navigation', 0, NULL, TRUE);
    $options = [];
    $user_terms = [];

    foreach ($terms as $term) {
      $tid = $term->id();
      $options[$tid] = '';
      $parents = array_reverse($this->termStorage->loadAllParents($tid));

      // Check if user is already assigned to term
      if ($this->isTermEditor($term, $user)) {
        $user_terms[] = $tid;
      }

      // Build checkbox array
      foreach ($parents as $idx => $parent) {
        $options[$tid] .= $idx == 0 ? $parent->getName() : ' / ' . $parent->getName();
      }
    }

    $form['#title'] = $this->t('Navigation sections for :user', [':user' => $user->getDisplayName()]);

    $form['sections'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sections'),
      '#description' => $this->t('Navigation sections'),
      '#required' => FALSE,
      '#options' => $options,
      '#default_value' => $user_terms
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
  public function submitForm(array &$form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    if (empty($uid = $form_state->get('uid'))) {
      return;
    }
    $account = $this->userStorage->load($uid);
    $sections_state = $form_state->getValue('sections');
    $res = null;

    foreach ($sections_state as $sk => $sv) {
      $isEditor = $this->isTermEditor($sk, $account);

      if (intval($sv) == 0) {
        if ($isEditor) {
          $term = $this->termStorage->load($sk);
          $field_navigation_editors = $term->get('field_navigation_editors')->getValue();
          $key = array_search($uid, array_column($field_navigation_editors, 'target_id'));
          $term->{'field_navigation_editors'}->removeItem($key);
          $res = true;
        }
      } else {
        if (!$isEditor) {
          $term = $this->termStorage->load($sk);
          $field_navigation_editors = $term->get('field_navigation_editors')->getValue();
          $term->{'field_navigation_editors'}[] = $uid;
          $res = true;
        }
      }
    }

    if($res !== null) {
      $term->save();
      \Drupal::messenger()->addMessage($this->t('User <strong>:user</strong> updated', [':user' => $account->getDisplayName()]));
    }
  }

  /**
   * Check if user is an Editor for term
   * 
   * @return bool
   */
  protected function isTermEditor($term, EntityInterface $user) {
    if (!$term instanceof Term) {
      $term = $this->termStorage->load($term);
    }

    $editors = array_column($term->field_navigation_editors->getValue(), 'target_id');

    // Check if user is already assigned to term
    if (in_array($user->id(), $editors)) {
      return TRUE;
    }

    return FALSE;
  }
}
