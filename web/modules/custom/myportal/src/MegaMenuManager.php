<?php

namespace Drupal\myportal;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MegaMenuManager, used for creating megamenu blocks.
 */
class MegaMenuManager implements MegaMenuManagerInterface {

  /**
   * The Entity type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * The language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $language;

  /**
   * The visible taxonomy service.
   *
   * @var \Drupal\myportal\VisibleTaxonomyInterface
   */
  private $visibleTaxonomy;

  /**
   * MegaMenuManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager service.
   * @param \Drupal\myportal\VisibleTaxonomyInterface $visible_taxonomy
   *   The visible taxonomy service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    LanguageManagerInterface $language,
    VisibleTaxonomyInterface $visible_taxonomy
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->language = $language;
    $this->visibleTaxonomy = $visible_taxonomy;
    /** @var \Drupal\taxonomy\TermStorageInterface termStorage */
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress InvalidArgument
   */
  public function getBlockMenuNodeId($tid): ?int {
    try {
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('field_navigation_section', $tid);
      $query->condition('type', 'block_mega_menu');
      $query->condition('status', 1);
      $query->sort('created', 'DESC');
      $query->range(0, 1);
      $results = $query->execute();

      if (!empty($results) && is_array($results)) {
        $results = reset($results);

        return intval($results);
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('MegaMenuManager service throw exception when read block menu node id: @message.', [
        '@message' => $e->getMessage(),
      ]);

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewBlockNode($tid): bool {
    $count = 0;
    $termsChild = $this->termStorage->loadTree('navigation', $tid, 1, TRUE);
    foreach ($termsChild as $term) {
      if ($term->hasField('field_hide_in_megamenu')
        && $term->get('field_hide_in_megamenu')->getString()) {
        continue;
      }

      if (!$this->visibleTaxonomy->hasVisibleTaxonomy($term->id())) {
        continue;
      }
      $count++;
    }

    return !($count > 3);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function menuItems($tid): array {
    $menu_item = [];
    $language = $this->language->getCurrentLanguage()->getId();

    /** @var \Drupal\taxonomy\Entity\Term[] $terms */
    $terms = $this->termStorage->loadTree('navigation', $tid, 1, TRUE);
    foreach ($terms as $term) {
      $url = "";
      $term_id = (int) $term->id();

      // Hide item in Megamenu.
      if ($term->hasField('field_hide_in_megamenu')
        && $term->get('field_hide_in_megamenu')->getString()) {
        continue;
      }

      // Check if the user has accessible content on the taxonomy in arguments.
      $visible_taxonomy = $this->visibleTaxonomy->hasVisibleTaxonomy($term_id);
      if (!$visible_taxonomy) {
        continue;
      }

      if ($term->hasTranslation($language)) {
        $term = $term->getTranslation($language);
      }
      $term_name = $term->label();

      if ($term->hasField('field_cover_images')) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $cover_image */
        $cover_image = $term->get('field_cover_images');
        /** @var \Drupal\file\Entity\File $entityReference */
        $entityReference = $cover_image->referencedEntities();
        $file = reset($entityReference);
        if (!empty($file) && !empty($file->getFileUri())) {
          $url = $file->getFileUri();
        }
      }

      // Retrieve the children items.
      $menu_children = $this->menuItemsChildren($term_id);

      $menu_item[] = [
        'tid' => $term_id,
        'name' => $term_name,
        'url_image' => $url,
        'children' => $menu_children,
      ];
    }

    return $menu_item;
  }

  /**
   * {@inheritdoc}
   */
  public function menuItemsChildren($tid): array {
    $menu_item = [];
    $language = $this->language->getCurrentLanguage()->getId();

    /** @var \Drupal\taxonomy\Entity\Term[] $terms */
    $terms = $this->termStorage->loadTree('navigation', $tid, 1, TRUE);
    foreach ($terms as $term) {
      $term_id = (int) $term->id();

      // Hide item in Megamenu.
      if ($term->hasField('field_hide_in_megamenu')) {
        if ($term->get('field_hide_in_megamenu')->getString()) {
          continue;
        }
      }

      // Check if the user has accessible content on the taxonomy in arguments.
      $visible_taxonomy = $this->visibleTaxonomy->hasVisibleTaxonomy($term_id);
      if (!$visible_taxonomy) {
        continue;
      }

      if ($term->hasTranslation($language)) {
        $term = $term->getTranslation($language);
      }
      $term_name = $term->label();
      // @phpstan-ignore-next-line
      $term_description = $term->getDescription();

      $menu_item[] = [
        'tid' => $term_id,
        'name' => $term_name,
        'description' => $term_description,
      ];
    }

    return $menu_item;
  }

}
