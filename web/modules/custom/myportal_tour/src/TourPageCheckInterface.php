<?php

namespace Drupal\myportal_tour;

/**
 * Interface TourPageCheckInterface, service for check tour in current page.
 */
interface TourPageCheckInterface {

  /**
   * Check if there is a tour for the current route.
   *
   * @return bool
   *   If there is a tour for the current page, it returns true.
   */
  public function issetTourPage(): bool;

}
