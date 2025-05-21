<?php

namespace Drupal\myportal_breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a breadcrumb builder for website.
 */
class MypBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $language;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * MypBreadcrumbBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->language = $language;
    /** @var \Drupal\taxonomy\TermStorageInterface termStorage */
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $routes = [
      'view.primary_navigation.page_1',
    ];

    return in_array($route_match->getRouteName(), $routes);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $links = [];
    $language = $this->language->getCurrentLanguage()->getId();

    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $links[] = Link::createFromRoute($this->t('Home'), '<none>');

    switch ($route_name) {
      case 'view.primary_navigation.page_1':
        $parameters = $route_match->getParameters()->all();
        $term_id = $parameters['arg_0'];
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_id);
        $breadcrumb->addCacheableDependency($term);

        $parents = $this->termStorage->loadAllParents($term_id);

        if ($parents) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
          foreach (array_reverse($parents) as $parent) {
            if ($parent->id() != $term_id) {
              $breadcrumb->addCacheableDependency($parent);
              if ($parent->hasTranslation($language)) {
                $parent = $parent->getTranslation($language);
              }

              $links[] = Link::createFromRoute($parent->label() ?? '', '<none>');

            }
          }
        }

        break;
    }

    return $breadcrumb->setLinks($links);
  }

}
