<?php

namespace Drupal\myportal;

use Drupal\facets\FacetInterface;

/**
 * Interface FacetsInformationInterface, services used for alter facet items.
 */
interface FacetsInformationInterface {

  /**
   * Facets label information.
   *
   * @param \Drupal\facets\FacetInterface $facets
   *   Facets.
   *
   * @return array
   *   Facets information.
   */
  public function facetsLabelInformation(FacetInterface $facets): array;

  /**
   * Returns the number of members within a group.
   *
   * @param int $gid
   *   Group id.
   *
   * @return int|string
   *   Returns the number of members within a group.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMemberGroup($gid);

}
