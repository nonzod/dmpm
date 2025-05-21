<?php

//@msg_clean

namespace Drupal\myportal\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\myaccess\UserManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the CheckContentForm class.
 *
 * @package Drupal\myportal\Form
 */
class CheckContentForm extends FormBase {

  /**
   * The group manager service.
   *
   * @var \Drupal\myaccess\GroupManagerInterface
   */
  protected $groupManager;

  /**
   * The user storage service.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The node storage service.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The term storage service.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The group storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageBase
   */
  protected $groupStorage;

  /**
   * The user manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * Construct new CheckContentForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\myaccess\GroupManagerInterface $group_manager
   *   The group manager.
   * @param \Drupal\myaccess\UserManagerInterface $user_manager
   *   The user manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupManagerInterface $group_manager, UserManagerInterface $user_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->groupStorage = $entity_type_manager->getStorage('group');
    $this->groupManager = $group_manager;
    $this->userManager = $user_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    $group_manager = $container->get('myaccess.group_manager');
    assert($group_manager instanceof GroupManagerInterface);

    $user_manager = $container->get('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);

    return new static($entity_type_manager, $group_manager, $user_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_check_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Retrieve info'),
      '#tree' => TRUE,
    ];
    $form['info']['node'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Content'),
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page', 'topic'],
      ],
    ];
    $form['info']['user'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User'),
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
    ];
    $form['info']['info'] = [
      '#type' => 'submit',
      '#value' => $this->t('Retrieve info'),
      '#submit' => ['::retrieveInfo'],
    ];

    foreach (['node', 'user'] as $type) {
      $type_groups = $form_state->has($type . '_groups') ? $form_state->get($type . '_groups') : [];
      if (!empty($type_groups)) {
        $form['info'][$type . '_groups_table'] = [
          '#type' => 'table',
          '#prefix' => '<h4>' . $this->t('Groups of %type', ['%type' => ucfirst($type)]) . '</h4>',
          '#header' => [
            'id' => $this->t('Id'),
            'title' => $this->t('Title'),
          ],
          '#rows' => $type_groups,
          '#empty' => $this->t('No group has been found.'),
        ];
      }
    }

    foreach (['node', 'user'] as $type) {
      $type_groups = $form_state->has($type . '_terms') ? $form_state->get($type . '_terms') : [];
      if (!empty($type_groups)) {
        $form['info'][$type . '_terms_table'] = [
          '#type' => 'table',
          '#prefix' => '<h4>' . $this->t('Terms of %type', ['%type' => ucfirst($type)]) . '</h4>',
          '#header' => [
            'id' => $this->t('Id'),
            'title' => $this->t('Title'),
          ],
          '#rows' => $type_groups,
          '#empty' => $this->t('No term has been found.'),
          '#description' => $type == 'user' ? $this->t('Terms where the user has role "edito"') : '',
        ];
      }
    }

    $results = $form_state->has('user_node_access') ? $form_state->get('user_node_access') : [];
    if (!empty($results)) {
      $form['info']['user_node_access_table'] = [
        '#type' => 'table',
        '#prefix' => '<h4>' . $this->t('Check Access throw CMS system API') . '</h4>',
        '#header' => [
          'op' => $this->t('Operation'),
          'result' => $this->t('Result'),
        ],
        '#rows' => $results,
      ];
    }

    $form['contents_user'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Retrieve Contents from User'),
      '#tree' => TRUE,
    ];
    $form['contents_user']['user'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Select User'),
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#description' => $this->t("<b>CAUTION WHEN USING</b>: requires a lot of resources to work!"),
    ];
    $form['contents_user']['check_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check entity access'),
    ];
    $form['contents_user']['retrieve'] = [
      '#type' => 'submit',
      '#value' => $this->t('Retrieve Content of User'),
      '#submit' => ['::retrieveContentsOfUser'],
    ];
    $contents_user = $form_state->has('contents_user') ? $form_state->get('contents_user') : [];
    if (!empty($contents_user)) {
      $form['contents_user']['table'] = [
        '#type' => 'table',
        '#header' => [
          'id' => $this->t('Id'),
          'title' => $this->t('Title'),
          'bundle' => $this->t('Bundle'),
          'status' => $this->t('Status'),
          'access' => $this->t('Access'),
        ],
        '#rows' => $contents_user,
        '#empty' => $this->t('No content has been found.'),
      ];
    }

    return $form;
  }

  /**
   * Form submission handler for the 'retrieve' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function retrieveContentsOfUser(array &$form, FormStateInterface $form_state) {
    $form_state->set('contents_user', []);
    $user_content = $form_state->getValue(['contents_user', 'user']);
    if (is_numeric($user_content) && $user = $this->userStorage->load($user_content)) {
      assert($user instanceof UserInterface);
      $contents = $this->getContentAccessUser($user, $form_state->getValue([
        'contents_user',
        'check_access',
      ]));
      $form_state->set('contents_user', $contents);
      $this->messenger()->addStatus(
        $this->t("The content of user are %count in total.", [
          '%count' => count($contents),
        ])
      );
    }
    $form_state->setRebuild();
  }

  /**
   * Retrieve the content of user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   * @param boolean $check_access
   *   The flag for check the entity access.
   *
   * @return array|void
   */
  protected function getContentAccessUser(UserInterface $user, $check_access = FALSE) {

    $contents = [];

    // Retrive the content that user is owner.
    $nids_owner = $this->nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition($this->nodeStorage->getEntityType()
        ->getKey('uid'), $user->id())
      ->execute();
    $nids_owner = is_array($nids_owner) ? $nids_owner : [];

    if (!empty($nids_owner)) {
      $nodes = $this->nodeStorage->loadMultiple($nids_owner);
    }

    // Retrieve the terms where user has role "editor".
    $user_term_ids = $this->userManager->getTermsIdNavigationThatUserIsEditor($user);

    // Retrieve the group where user is member (editor role).
    $user_group_ids = $this->groupManager->getGroupIdsByUserAndRole($user, 'editor');
    $user_groups = $this->groupStorage->loadMultiple($user_group_ids);

    foreach ($user_groups as $group) {
      assert($group instanceof GroupInterface);

      // For each group retrieve the contents.
      foreach ($group->getContent() as $group_content) {
        $content = $group_content->getEntity();
        if (!($content instanceof ContentEntityInterface
          && $content->bundle() == 'page'
          && $content->hasField('field_navigation_section'))) {
          continue;
        }
        $navigation_section_values = $content->get('field_navigation_section')
          ->getValue();
        $navigation_section_values = array_column($navigation_section_values, 'target_id');

        // Keep the content (node) that have a same terms of user.
        if (!empty(array_intersect($user_term_ids, $navigation_section_values))) {
          $nodes[$content->id()] = $content;
        }
      }
    }


    // Check access.
    foreach ($nodes as $node) {
      assert($node instanceof NodeInterface);

      $op_res = [];
      if ($check_access) {
        foreach (['view', 'update', 'delete'] as $op) {
          $res = $node->access($op, $user, TRUE);
          $op_res[] = "{$op}: " . ($res->isAllowed() ? $this->t('allowed') : $this->t('not allowed'));
        }
      }

      $contents[$node->id()] = [
        $node->id(),
        $node->label(),
        $node->bundle(),
        $node->isPublished() ? $this->t("Published") : $this->t("Not published"),
        [
          'data' => [
            '#markup' => implode("<br>", $op_res),
          ],
        ],
      ];
    }

    ksort($contents);

    return $contents;
  }

  /**
   * Form submission handler for the 'info' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function retrieveInfo(array &$form, FormStateInterface $form_state) {

    foreach (['user', 'node'] as $lv1) {
      foreach (['groups', 'terms'] as $lv2) {
        $form_state->set("{$lv1}_{$lv2}", NULL);
      }
    }
    $form_state->set('user_node_access', NULL);

    if ($form_state->hasValue(['info', 'node'])) {
      $value = $form_state->getValue(['info', 'node']);
      $node = $this->nodeStorage->load($value);
      assert($node instanceof NodeInterface);
      $this->retrieveInfoNode($node, $form_state);
    }

    if ($form_state->hasValue(['info', 'user'])) {
      $value = $form_state->getValue(['info', 'user']);
      $user = $this->userStorage->load($value);
      assert($user instanceof UserInterface);
      $this->retrieveInfoUser($user, $form_state);
    }


    if (isset($node) && isset($user)) {
      $results = [];
      foreach (['view', 'update', 'delete'] as $op) {
        $access = $node->access($op, $user, TRUE);
        $results[] = [
          'op' => $op,
          'result' => $access->isAllowed() ? 'Allowed' : 'Not allowed',
        ];
      }
      $form_state->set('user_node_access', $results);
    }

    $form_state->setRebuild();
  }

  /**
   * Retrive info for node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function retrieveInfoNode(NodeInterface $node, FormStateInterface $form_state) {
    $node_group_ids = $this->groupManager->getGroupIdsByNode($node);
    if (!empty($node_group_ids)) {
      $groups = $this->groupStorage->loadMultiple($node_group_ids);
      $node_group = [];
      foreach ($groups as $group) {
        $node_group[$group->id()] = [
          $group->id(),
          $group->label(),
        ];
      }
      $form_state->set('node_groups', $node_group);
    }

    if ($node->hasField('field_navigation_section')) {
      $node_term_ids = $node->get('field_navigation_section')->getValue();
      $node_term_ids = array_column($node_term_ids, 'target_id');
      $node_terms = [];
      foreach ($this->termStorage->loadMultiple($node_term_ids) as $term) {
        $node_terms[$term->id()] = [
          $term->id(),
          $term->label(),
        ];
      }
      $form_state->set('node_terms', $node_terms);
    }
  }

  /**
   * Retrieve info for user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function retrieveInfoUser(UserInterface $user, FormStateInterface $form_state) {
    $user_group_ids = $this->groupManager->getGroupIdsByUserAndRole($user, 'editor');
    if (!empty($user_group_ids)) {
      $groups = $this->groupStorage->loadMultiple($user_group_ids);
      $user_group = [];
      foreach ($groups as $group) {
        $user_group[$group->id()] = [
          $group->id(),
          $group->label(),
        ];
      }
      $form_state->set('user_groups', $user_group);
    }

    // Retrieve the taxonomy where the current user is "editor".
    $user_term_ids = $this->userManager->getTermsIdNavigationThatUserIsEditor($user);
    $user_terms = [];
    foreach ($this->termStorage->loadMultiple($user_term_ids) as $term) {
      $user_terms[$term->id()] = [
        $term->id(),
        $term->label(),
      ];
    }
    $form_state->set('user_terms', $user_terms);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing.
  }

}
