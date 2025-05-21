<?php

namespace Drupal\myportal\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a Block Navigation Item block.
 *
 * @Block(
 *   id = "myp_block_navigation",
 *   admin_label = @Translation("Myportal Navigation"),
 *   category = @Translation("Myportal block"),
 * )
 */
class BlockNavigationItem extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The current active route match object.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * The routeMatch.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * BlockNavigationItem constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   LanguageManager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   EntityRepository service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current active route match object.
   */
  final public function __construct(array $configuration,
                                    $plugin_id,
                                    $plugin_definition,
                                    LanguageManagerInterface $language_manager,
                                    EntityTypeManagerInterface $entity_type_manager,
                                    EntityRepositoryInterface $entity_repository,
                                    RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->language = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    $language_manager = $container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);

    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    $entity_repository = $container->get('entity.repository');
    assert($entity_repository instanceof EntityRepositoryInterface);

    $route = $container->get('current_route_match');
    assert($route instanceof RouteMatchInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $language_manager,
      $entity_type_manager,
      $entity_repository,
      $route
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $current_parameters = $this->routeMatch->getParameters();
    $item_active = !empty($current_parameters->get('arg_0')) ? $current_parameters->get('arg_0') : '';

    $terms = $this->getTaxonomyTermsSortedByWeight('navigation');

    return [
      '#theme' => 'block_navigation',
      '#terms' => $terms,
      '#item_active' => $item_active,
      '#cache' => [
        'max-age' => 0,
        'contexts' => $this->getCacheContexts(),
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(),
      ['url.path', 'url.query_args']);
  }

  /**
   * Get taxonomy terms sorted by weight.
   *
   * @param string $vid
   *   The vocabulary name.
   *
   * @return array
   *   Returns an array of term id | name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getTaxonomyTermsSortedByWeight($vid) {

    // Initialize the items.
    $items = [];

    $language = $this->language->getCurrentLanguage()->getId();

    // Get the term storage.
    $entity_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Query the terms sorted by weight.
    $query_result = $entity_storage->getQuery()
      ->condition('vid', $vid)
      ->condition('parent', 0)
      ->sort('weight', 'ASC')
      ->execute();

    // Load the terms.
    if (is_array($query_result) && !empty($query_result)) {
      $terms = $entity_storage->loadMultiple($query_result);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $term */
      foreach ($terms as $term) {
        $tid = (int) $term->id();
        $term_name = $term->label();
        if ($term->hasTranslation($language)) {
          $translated_term = $this->entityRepository
            ->getTranslationFromContext($term, $language);
          $term_name = $translated_term->label();
        }

        $items[$tid] = $term_name;
      }

      // Return the items.
      return $items;
    }

    return $items;
  }

}
