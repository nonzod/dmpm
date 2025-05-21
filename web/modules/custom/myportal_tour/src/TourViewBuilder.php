<?php

namespace Drupal\myportal_tour;

use Drupal\tour\TourViewBuilder as OriginalTourViewBuilder;

/**
 * Override the label of the button to enable translation.
 *
 * Remove when the issue drupal/issues/3099385 will close or will update
 * Drupal core version and apply patch.
 *
 * @package Drupal\myportal_tour
 */
class TourViewBuilder extends OriginalTourViewBuilder {

  /**
   * {@inheritDoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = parent::viewMultiple($entities, $view_mode, $langcode);

    /** @var \Drupal\tour\TourInterface $entity */
    foreach ($entities as $entity_id => $entity) {
      if (isset($build[$entity_id])) {
        $items = $build[$entity_id]['#items'];
        $count = count($items ?? []);

        foreach ($items ?? [] as $key => $item) {
          if (--$count <= 0) {
            // Preserve last element.
            break;
          }
          $build[$entity_id]['#items'][$key]['#wrapper_attributes']['data-text'] = $this->t('Next');
        }
      }

      return $build;
    }
  }

}
