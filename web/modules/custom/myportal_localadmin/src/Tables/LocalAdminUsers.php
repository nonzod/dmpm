<?php

namespace Drupal\myportal_localadmin\Tables;

use Drupal\Core\Url;

/**
 * List of users in the local admin groups
 */
class LocalAdminUsers {

  /**
   * Fetch the list
   * 
   * @var mixed
   */
  protected function fetchQuery() {
    $memberships = \Drupal::service('group.membership_loader')->loadByUser();
    $gids = [];

    foreach ($memberships as $membership) {
      $group = $membership->getGroup();
      $user = $membership->getUser();

      if ($group->hasPermission("flexible_group-local_admin", $user)) {
        $gids[] = $group->id->getString();
      }
    }

    $database = \Drupal::database();
    $query = $database->select('group_content_field_data', 'gc', []);
    $query->fields('gc', ['gid', 'entity_id', 'label', 'id'])
      ->condition('gc.type', 'flexible_group-group_membership')
      ->condition('gid', $gids, 'IN')
      ->condition('entity_id', 1, '!=') // Exclude admin
      ->orderBy('label', 'ASC');

    $query->leftJoin('groups_field_data', 'g', 'g.id = gc.gid');
    $query->addField('g', 'label', 'group_name');

    $label = \Drupal::request()->query->get('name');
    $gid = \Drupal::request()->query->get('gid');
    if (!empty($label)) {
      $query->condition('gc.label', "%$label%", 'LIKE');
    }
    if (!empty($gid)) {
      $query->condition('gc.gid', $gid);
    }

    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);

    return $pager->execute();
  }

  /**
   * Build array rows for the Drupal renderable array
   * 
   * @var array
   */
  protected function getRows() {
    $result = $this->fetchQuery();
    $rows = [];

    foreach ($result as $record) {
      $row = [];
      $row[] = $record->label;
      $row[] = $record->group_name;
      $row[] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#type' => 'link',
                '#title' => 'Manage',
                '#url' =>Url::fromRoute('myportal_localadmin.group_role_edit', ['member' => $record->id, 'group' => $record->gid]),
              ],
            ],
          ],
        ],
      ];

      $row[] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#type' => 'link',
                '#title' => 'Manage',
                '#url' => Url::fromRoute('myportal_localadmin.group_user_edit', ['user' => $record->entity_id]),
              ],
            ],
          ],
        ],
      ];

      $row[] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#type' => 'link',
                '#title' => 'Manage',
                '#url' => Url::fromRoute('myportal_localadmin.section_user_edit', ['user' => $record->entity_id]),
              ],
            ],
          ],
        ],
      ];

      $row[] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#type' => 'link',
                '#title' => 'masquerade',
                '#url' => Url::fromRoute('entity.user.masquerade', ['user' => $record->entity_id]),
              ],
            ],
          ],
        ],
      ];
      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * Returns Drupal renderable array
   * 
   * @var array
   */
  public function getRenderableArray() {
    return [
      '#title' => t('Users in my groups'),
      'form' => \Drupal::formBuilder()->getForm('Drupal\myportal_localadmin\Form\LocalAdminMembersFilterForm'),
      'table' => [
        '#type' => 'table',
        '#header' => [
          'uid' => [
            'data' => t('Member'),
            'specifier' => 'uid',
          ],
          'group' => [
            'data' => t('Group'),
            'specifier' => 'gid',
          ],
          'group_role' => [
            'data' => t('Group role'),
            'specifier' => 'uid',
          ],
          'system_role' => [
            'data' => t('Drupal role'),
            'specifier' => 'uid',
          ],
          'navigation_terms' => [
            'data' => t('Sections'),
            'specifier' => 'uid',
          ],
          'actions' => [
            'data' => t('Actions'),
            'specifier' => 'uid',
          ]
        ],
        '#rows' => $this->getRows(),
        '#empty' => t('No Users found.'),
      ],
      'pager' => [
        '#type' => 'pager',
      ]
    ];
  }
}
