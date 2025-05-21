<?php

namespace Drupal\myportal;

/**
 * Class MegaMenuManagerInterface, used for block menu item.
 */
interface MegaMenuManagerInterface {

  /**
   * Get the node id to use in the drupal_entity in the block megamenu.
   *
   * @param int $tid
   *   Taxonomy term id.
   *
   * @return int|null
   *   Return the entity id.
   */
  public function getBlockMenuNodeId($tid): ?int;

  /**
   * Implement the check to show or not the block in the megamenu.
   *
   * @param int $tid
   *   Taxonomy term id.
   *
   * @return bool
   *   Returns true if the taxonomy has less than 4 daughters.
   */
  public function viewBlockNode($tid): bool;

  /**
   * Return an array with taxonomy data for the 2 level menu.
   *
   * @param int $tid
   *   Taxonomy term id.
   *
   * @return array
   *   Return the array to compose the 2nd level menu.
   */
  public function menuItems($tid): array;

  /**
   * Return an array with taxonomy data for the 3 level menu.
   *
   * @param int $tid
   *   Taxonomy term id.
   *
   * @return array
   *   Return the array to compose the 3nd level menu
   */
  public function menuItemsChildren($tid): array;

}
