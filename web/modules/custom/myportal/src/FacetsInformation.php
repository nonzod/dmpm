<?php

namespace Drupal\myportal;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\FacetInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Class FacetsInformation, used for alter facet items.
 */
class FacetsInformation implements FacetsInformationInterface {

  /**
   * The type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * FacetsInformation constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $type_manager
   *   The Entity Type Manager.
   */
  public function __construct(EntityTypeManagerInterface $type_manager) {
    $this->typeManager = $type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function facetsLabelInformation(FacetInterface $facets): array {
    $results = $facets->getResults();
    $logic = [];
    foreach ($results as $result) {
      $id = $result->getFacet()->id();
      $entity_id = (int) $result->getRawValue();
      switch ($id) {

        case 'country':
        case 'function':
        case 'subfunction':
        case 'legal_entity':
          $logic['group'][$entity_id] = $this->getMemberGroup($entity_id);

          break;
      }
    }

    return $logic;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberGroup($gid) {
    $group = $this->typeManager->getStorage('group')
      ->load($gid);
    if (!$group instanceof GroupInterface) {
      return '';
    }
    $memberships = count($group->getMembers());

    return $memberships;
  }

}
