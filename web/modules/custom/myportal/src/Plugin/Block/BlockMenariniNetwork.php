<?php

namespace Drupal\myportal\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BlockMenariniNetwork' block.
 *
 * @Block(
 *  id = "myp_menarini_network",
 *  admin_label = @Translation("Menarini Network"),
 *  category = @Translation("Myportal block"),
 * )
 */
class BlockMenariniNetwork extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#theme' => 'block-menarini-network',
    ];
  }

}
