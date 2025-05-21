<?php

namespace Drupal\myportal_theme\Plugin\Preprocess;

use Drupal\socialbase\Plugin\Preprocess\Block as BlockBase;

/**
 * Pre-processes variables for the "block" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("block")
 */
class Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);
    if ($variables['elements']['#plugin_id'] === 'myp_taxonomy_list_block'
    || $variables['elements']['#plugin_id'] === 'myp_linkedin_block'
    || $variables['elements']['#plugin_id'] === 'myp_gsuite_results_search'
    || $variables['elements']['#plugin_id'] === 'myp_menarini_network'
    || $variables['elements']['#plugin_id'] === 'myportal_news_feed'
    || $variables['elements']['#plugin_id'] === 'myp_megamenu_block') {
      unset($variables['content']['#attributes']);
      $variables['card'] = FALSE;
    }
  }

}
