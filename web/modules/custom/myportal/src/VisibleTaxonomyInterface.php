<?php

namespace Drupal\myportal;

/**
 * Class VisibleTaxonomyInterface, check if the user has accessible content.
 */
interface VisibleTaxonomyInterface {

  /**
   * Check if the user has accessible content on the taxonomy in arguments.
   *
   * @param int $term_id
   *   Taxonomy id, used for arguments in views.
   *
   * @return bool
   *   Returns True if the current user has content to view.
   */
  public function hasVisibleTaxonomy(int $term_id): bool;

}
