<?php

namespace Drupal\myportal_localadmin\Tables;

use Drupal\Core\Url;

/**
 * List of users in the local admin groups
 */
class LocalAdminGroups {

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
    $query = $database->select('groups_field_data', 'g', []);
    $query->fields('g', ['id', 'label'])
      ->condition('type', 'flexible_group')
      ->condition('id', $gids, 'IN')
      ->orderBy('label', 'ASC');

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

    foreach ($result as $record) {
      $row = [];
      $row[] = $record->label;
      $row[] = [
        'data' => [
          'label' => [
            'data' => [
              'link' => [
                '#type' => 'link',
                '#title' => 'Show',
                '#url' => Url::fromRoute("myportal_localadmin.group_users_overview", ['gid' => $record->id])
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
                '#title' => 'Add member',
                '#url' => Url::fromRoute("myportal_localadmin.add_group_user", [
                  'group' => $record->id, 
                  'plugin_id' => 'group_membership'
                ], [
                  'query' => ['destination' => '/admin/local-admin/groups']
                ])
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
      '#title' => t('My groups'),
      //'form' => $form,
      'table' => [
        '#type' => 'table',
        '#header' => [
          'uid' => [
            'data' => t('Group'),
            'specifier' => 'uid',
          ],
          'gid' => [
            'data' => t('Members'),
            'specifier' => 'gid',
          ],
          'add' => [
            'data' => t('Action'),
            'specifier' => 'gid',
          ]
        ],
        '#rows' => $this->getRows(),
        '#empty' => t('No Groups found.'),
      ],
      'pager' => [
        '#type' => 'pager',
      ]
    ];
  }
}
