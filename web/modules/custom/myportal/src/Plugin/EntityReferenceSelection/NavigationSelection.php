<?php

namespace Drupal\myportal\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\myportal\Access\MyPortalAccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an entity reference selection for Navigation taxonomy terms.
 *
 * @EntityReferenceSelection(
 *   id = "myportal_navigation",
 *   label = @Translation("MyPortal: Filter Navigation sections by editor permissions"),
 *   entity_types = {"taxonomy_term"},
 *   group = "myportal_navigation",
 *   weight = 0
 * )
 */
class NavigationSelection extends SelectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new NavigationSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->buildEntityQueryForNavigation();

    if ($limit > 0) {
      $query->range(NULL, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $itemsList = [];
    $options = [];

    if (is_array($result)) {
      $itemsList = $this->buildItemsList($result);
    }

    foreach ($itemsList as $item) {
      $options['navigation'][$item['tid']] = $item['label'];
    }

    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildEntityQueryForNavigation();

    return (int) $query
      ->count()
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];

    if ($ids) {
      $query = $this->buildEntityQueryForNavigation();
      $result = $query
        ->condition('tid', $ids, 'IN')
        ->execute();
    }

    return (array) $result;
  }

  /**
   * Builds an EntityQuery to get referenceable navigation entities.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query object that can query the given entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildEntityQueryForNavigation(): QueryInterface {
    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery();
    $query->condition('vid', 'navigation');

    if (empty(array_intersect(MyPortalAccessResult::NO_RESTRICTED_ROLES, $this->currentUser->getRoles()))) {
      $query->condition('field_navigation_editors', $this->currentUser->id(), 'CONTAINS');
    }

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    return $query;
  }

  /**
   * Build ordered selection list.
   *
   * @param array $term_ids
   *   Terms ids.
   *
   * @return array
   *   Ordered list of items ['tid', 'label'].
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildItemsList(array $term_ids): array {
    $options = [];
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    foreach ($term_ids as $term_id) {
      $parents = $termStorage->loadAllParents($term_id);

      /** @var \Drupal\taxonomy\TermInterface $parentTerm */
      $labels = [];
      foreach ($parents as $parentTerm) {
        $labels[] = $parentTerm->label();
      }

      $labels = array_reverse($labels);
      $options[] = [
        'tid' => $term_id,
        'label' => implode(' / ', $labels),
      ];
    }

    usort($options, static function ($first, $second) {
      return strcmp($first['label'], $second['label']);
    });

    return $options;
  }

}
