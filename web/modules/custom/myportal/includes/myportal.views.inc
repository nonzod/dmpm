<?php

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Implements hook_views_post_render().
 */
function myportal_views_post_render(ViewExecutable $view): void {
  if ($view->id() !== 'primary_navigation'
    && $view->getDisplay()->getPluginId() !== 'page_1') {
    return;
  }

  $request = \Drupal::request();

  if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
    $new_title = $view->getTitle();
    $route->setDefault('_title', $new_title);
  }
}

/**
 * Implements hook_views_pre_view().
 */
function myportal_views_pre_view(ViewExecutable $view, string $display_id, array &$args): void {

  if ($view->id() === 'navigation_terms'
    && $display_id === 'entity_reference_terms') {
    $no_restriction_roles = [
      'administrator',
      'sitemanager',
      'contentmanager',
    ];
    $current_user = \Drupal::currentUser();
    $user_roles = $current_user->getRoles();
    if (!empty(array_intersect($no_restriction_roles, $user_roles))) {
      $args[] = 'all';
    }
  }
}

/**
 * Implements hook_views_query_alter().
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
function myportal_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {

  // Add a filter to show only future events of type channel streaming if no
  // filters in the query string are set.
  if ($view->id() === 'events_list' && $view->current_display == 'channel_streaming' && $query instanceof Sql) {
    // Get filters from the query string.
    $current_request = \Drupal::requestStack()->getCurrentRequest();
    $filter_date_value = $current_request ? $current_request->get('field_event_date_value') : NULL;

    $offset = $query->getTimezoneOffset();
    $field_event_date = 'node__field_event_date.field_event_date_value';
    $query->setFieldTimezoneOffset($field_event_date, $offset);
    $field_event_date_end = 'node__field_event_date_end.field_event_date_end_value';
    $query->setFieldTimezoneOffset($field_event_date_end, $offset);
    $date_format = 'Y-m-d H:i:s';

    if (!$filter_date_value) {
      $date_time = new DateTime();

      $now = "'" . $date_time->format($date_format) . "'";

      // Add a join with the node__field_event_date_end table.
      $definition = [
        'table' => 'node_field_data',
        'field' => 'nid',
        'left_table' => 'node__field_event_date_end',
        'left_field' => 'entity_id',
      ];
      $join = \Drupal::service('plugin.manager.views.join')
        ->createInstance('standard', $definition);
      $query->ensureTable('field_event_date_end_value', NULL, $join);

      // Filter for events that happens now or are in the future.
      // (now <= start_date OR end_date >= now).
      $query->addWhereExpression(
        '',
        sprintf('%s <= %s OR %s >= %s',
          // Today.
          $query->getDateFormat($now, $date_format),
          // Start date.
          $query->getDateFormat($field_event_date, $date_format),
          // End date.
          $query->getDateFormat($field_event_date_end, $date_format),
          // Today.
          $query->getDateFormat($now, $date_format)
        )
      );
    }
    else {
      $filter_date_end_value = $current_request ? $current_request->get('field_event_date_end_value') : NULL;
      if ($filter_date_end_value != NULL) {
        $date_end_time = DateTime::createFromFormat('!m/d/Y', $filter_date_end_value);
        $date_end_time->add(new DateInterval('PT23H59M'));
        $date_end_value_end_day = "'" . $date_end_time->format($date_format) . "'";

        $date_time = DateTime::createFromFormat('!m/d/Y', $filter_date_value);
        $date_value_end_day = "'" . $date_time->format($date_format) . "'";

        /* Filter for events that match one of those cases:
        /
        /   range start     range end
        /       |               |
        /  *-------------------------* (A)
        /       |               |
        /       |  *------* (B) |
        /       |               |
        /       |      *--------------* (C)
        /       |               |
        /  *---------* (D)      |
        /       |               |
        /
        / So we need to filter for events with:
        / start_date <= range end AND end_date >= range start
        /
        / WARNING: this code assumes that the filters for the start and end
        / dates are the 4th and the 5h in the configuration of the view. */
        $query->where[1]['conditions'][3]['field'] = sprintf(
          '%s <= %s',
          $query->getDateFormat($field_event_date, $date_format),
          $query->getDateFormat($date_end_value_end_day, $date_format)
        );

        $query->where[1]['conditions'][4]['field'] = sprintf(
          '%s >= %s',
          $query->getDateFormat($field_event_date_end, $date_format),
          $query->getDateFormat($date_value_end_day, $date_format)
        );
      }
    }
  }
}
