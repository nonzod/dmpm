<?php

namespace Drupal\myportal;

use Drupal\taxonomy\Entity\Term;

/**
 * Interface GetChildTermsInterface, service for GetChildTerms.
 */
interface GetChildTermsInterface {

  /**
   * Get depth taxonomy.
   *
   * @param int $tid
   *   Taxonomy id.
   *
   * @return mixed
   *   Return depth taxonomy.
   */
  public function getDepthTerm($tid);

  /**
   * Get tree taxonomy by term id.
   *
   * @param int $tid
   *   Taxonomy id.
   *
   * @return array
   *   Array with the terms to show.
   */
  public function loadTree($tid): array;

  /**
   * I check if the term has any daughter taxonomies.
   *
   * @param int $tid
   *   Taxonomy id.
   *
   * @return bool
   *   Return TRUE if taxonomy has daughter taxonomies.
   */
  public function checkChildTerms($tid): bool;

  /**
   * Get tree taxonomy by term id.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   Taxonomy term.
   *
   * @return array
   *   Return the information taxonomy child.
   */
  public function termsChild(Term $term): array;

}
