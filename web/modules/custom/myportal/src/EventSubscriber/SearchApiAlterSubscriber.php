<?php

namespace Drupal\myportal\EventSubscriber;

use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;

/**
 * Defines the SearchApiAlterSubscriber class.
 *
 * Alter the search api query for views attachment.
 *
 * @package Drupal\myportal\EventSubscriber
 */
class SearchApiAlterSubscriber implements EventSubscriberInterface {

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $faceManager;

  /**
   * Construct new SearchApiAlterSubscriber instance.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The Default Facet Manager.
   */
  public function __construct(DefaultFacetManager $facet_manager) {
    $this->faceManager = $facet_manager;
  }

  /**
   * Alter the query facets seals attachment.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The query alter event.
   */
  public function alterQueryPreExecute(QueryPreExecuteEvent $event) {
    $query = $event->getQuery();
    $search_id = $query->getSearchId();

    // If we find an attachment view query, we use the same query alter as
    // the page because they belong together.
    if (!empty($search_id) && strpos($search_id, 'views_attachment:search__') === 0) {
      $search_id = 'search_api:views_page__search__page_2';
      $this->faceManager->alterQuery($query, $search_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'alterQueryPreExecute',
    ];
  }

}
