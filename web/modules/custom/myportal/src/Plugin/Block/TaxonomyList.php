<?php

namespace Drupal\myportal\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a Taxonomy List block.
 *
 * @Block(
 *   id = "myp_taxonomy_list_block",
 *   admin_label = @Translation("Taxonomy List block"),
 *   category = @Translation("Myportal block"),
 * )
 */
class TaxonomyList extends BlockBase implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['lazyBuild', 'build'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#lazy_builder' => [static::class . '::lazyBuild', []],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * LazyBuild render block.
   *
   * @return array
   *   Rendable array.
   */
  public static function lazyBuild(): array {
    $current_parameters = \Drupal::service('current_route_match')->getParameters();
    $tid = $current_parameters->get('arg_0');

    /** @var \Drupal\myportal\GetChildTerms $terms_service */
    $terms_service = \Drupal::service('myportal.get_child_terms');
    $terms = $terms_service->loadTree($tid);

    return [
      '#theme' => 'taxonomy_list',
      '#terms' => $terms,
      '#item_active' => $tid,
      '#cache' => [
        'tags' => ["taxonomy_term:$tid"],
        'contexts' => ['url.path', 'url.query_args', 'languages', 'user'],
      ],
    ];
  }

}
