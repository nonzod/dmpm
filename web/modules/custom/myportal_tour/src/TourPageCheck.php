<?php

namespace Drupal\myportal_tour;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * This is used to check if a tour is active on the current route.
 */
class TourPageCheck implements TourPageCheckInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TourPageCheck constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current active route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Check if there is a tour for the current route.
   *
   * @return bool
   *   If there is a tour for the current page, it returns true.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function issetTourPage(): bool {
    $route_name = $this->currentRouteMatch->getRouteName();

    $results = $this->entityTypeManager->getStorage('tour')->loadByProperties(['routes.*.route_name' => $route_name]);

    if (is_array($results) && !empty($results)) {
      return TRUE;
    }

    return FALSE;
  }

}
