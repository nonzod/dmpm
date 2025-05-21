<?php

namespace Drupal\myportal;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class GetChildTerms, used this class for return all terms by term id.
 */
class GetChildTerms implements GetChildTermsInterface {

  /**
   * The language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * The type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The visible service.
   *
   * @var \Drupal\myportal\VisibleTaxonomyInterface
   */
  private $visibleTaxonomy;

  /**
   * GetChildTerms constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\myportal\VisibleTaxonomyInterface $visible_taxonomy
   *   The visible taxonomy service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    LanguageManagerInterface $language,
    EntityTypeManagerInterface $entity_type_manager,
    VisibleTaxonomyInterface $visible_taxonomy
  ) {
    $this->language = $language;
    $this->entityTypeManager = $entity_type_manager;
    $this->visibleTaxonomy = $visible_taxonomy;
    /** @var \Drupal\taxonomy\TermStorageInterface termStorage */
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * Get tre taxonomy by term id.
   *
   * @param int $tid
   *   The taxonomy id.
   *
   * @return array
   *   Array with the terms to show.
   */
  public function loadTree($tid): array {
    $terms = $children = [];
    $taxonomyDepth = $this->getDepthTerm($tid);
    $language = $this->language->getCurrentLanguage()->getId();
    $block_title = "";

    $term_buffer = $this->termStorage->load($tid);
    if (!empty($term_buffer)) {
      $block_title = $term_buffer->label();
    }

    if ($taxonomyDepth < 3) {
      return $terms;
    }

    if (!$this->checkChildTerms($tid)) {
      return $terms;
    }

    $termsItem = $this->termStorage->loadChildren($tid);
    $terms['title_block'] = $block_title;
    foreach ($termsItem as $term) {
      $termId = (int) $term->id();
      $children = [];
      if (!$this->visibleTaxonomy->hasVisibleTaxonomy($termId)) {
        continue;
      }

      $termsChild = $this->termStorage->loadTree(
        'navigation',
        $termId,
        3,
        TRUE
      );

      if (!empty($termsChild)) {
        foreach ($termsChild as $termChild) {
          /** @var \Drupal\taxonomy\Entity\Term $termChild */
          $children[] = $this->termsChild($termChild);
        }
      }

      if ($term->hasTranslation($language)) {
        $term = $term->getTranslation($language);
      }
      $termName = $term->label();

      usort($children, function ($term1, $term2) {
        return $term1['parent'] <=> $term2['parent'];
      });

      $terms['items'][] = [
        'tid' => $termId,
        'name' => $termName,
        'children' => $children,
      ];

    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepthTerm($tid) {
    $terms = $this->termStorage->loadAllParents($tid);
    if ($terms && !empty($terms)) {

      return count($terms);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkChildTerms($tid): bool {
    $termsChild = $this->termStorage->loadTree(
      'navigation',
      $tid,
      1,
      TRUE
    );

    return !empty($termsChild) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function termsChild($terms): array {
    $children = [];
    $language = $this->language->getCurrentLanguage()->getId();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $terms */
    $termChildId = (int) $terms->id();
    if ($this->visibleTaxonomy->hasVisibleTaxonomy($termChildId)) {
      if ($terms->hasTranslation($language)) {
        $terms = $terms->getTranslation($language);
      }

      $termChildName = $terms->label();

      $children = [
        'tid' => $termChildId,
        'name' => $termChildName,
        'parent' => $this->getDepthTerm($termChildId),
      ];
    }

    return $children;
  }

}
