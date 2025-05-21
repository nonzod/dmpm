<?php

namespace Drupal\myportal\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a GSuite Results Search block.
 *
 * @Block(
 *   id = "myp_gsuite_results_search",
 *   admin_label = @Translation("GSuite Results Search"),
 *   category = @Translation("Myportal block"),
 * )
 */
class GsuiteResultsSearch extends BlockBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'gsuite_results',
    ];
  }

}
