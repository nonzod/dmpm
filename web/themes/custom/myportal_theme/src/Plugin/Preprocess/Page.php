<?php

namespace Drupal\myportal_theme\Plugin\Preprocess;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\socialbase\Plugin\Preprocess\Page as PageBase;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends PageBase implements ContainerFactoryPluginInterface {

  /**
   * The LanguageManagerInterface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EntityRepositoryInterface.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The RouteMatchInterface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Page constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity reposityory definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current active route match object.
   */
  final public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              LanguageManagerInterface $language,
                              EntityRepositoryInterface $entity_repository,
                              RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language;
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

    $entity_reposityory = $container->get('entity.repository');
    assert($entity_reposityory instanceof EntityRepositoryInterface);

    $route_match = $container->get('current_route_match');
    assert($route_match instanceof RouteMatchInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $language_manager,
      $entity_reposityory,
      $route_match
    );
  }

  /**
   * {@inheritdoc}
   *
   *   @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    $variables['hero_styled_image_url'] = '';
    $variables['hero_video_url'] = '';
    $variables['title'] = '';
    $route_match = $this->routeMatch;

    // Set cover image for primary section for example: /page-navigation/6.
    $current_parameters = $route_match->getParameters();
    $tid = $current_parameters->get('arg_0');
    if ($current_parameters->get('view_id') === 'primary_navigation') {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $term */
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
      if ($term instanceof TermInterface) {
        $variables['hero_styled_image_url'] = $this->getImageCover($term, 'men_hero_large', 'field_cover_images');

        $cover_video = $this->getCoverVideo($term);
        if (!empty($cover_video)) {
          $variables['hero_styled_image_url'] = '';
          $variables['hero_video_url'] = $cover_video;
        }
        $variables['title'] = $this->getTermName($term);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getImageCover(TermInterface $entity, $image_style, $image_field) {
    $url_image = '';
    if ($entity->hasField($image_field) && !empty($entity->{$image_field}->entity)) {
      $image_path = $entity->{$image_field}->entity->getFileUri();
      $url_image = \Drupal::service('file_url_generator')->generateAbsoluteString($image_path);
      if (isset($image_path)) {
        $style = ImageStyle::load($image_style); // phpcs:ignore
        if ($style != NULL) {
          $url_image = $style->buildUrl($image_path);
        }
      }
    }

    return $url_image;
  }

  /**
   * {@inheritdoc}
   */
  private function getCoverVideo(TermInterface $entity): string {
    $url_video = '';
    if ($entity->hasField('field_cover_video')) {
      $mid = $entity->get('field_cover_video')->getString();
      if (!empty($mid)) {
        $media = $this->entityTypeManager->getStorage('media')->load($mid);
        /** @var \Drupal\media\Entity\Media $media */
        if ($media->hasField('field_media_video_file') && !empty($media->field_media_video_file->entity)) {
          $media_url = $media->field_media_video_file->entity->getFileUri();
          $url_video = \Drupal::service('file_url_generator')->generateAbsoluteString($media_url);
        }
      }
    }

    return $url_video;
  }

  /**
   * {@inheritdoc}
   */
  public function getTermName(TermInterface $entity) {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $term_name = $entity->label();
    // Set the entity in the correct language for display.
    if ($entity instanceof TranslatableInterface) {
      $entity = $this->entityRepository->getTranslationFromContext($entity, $language);
      $term_name = $entity->label();
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if ($entity->hasTranslation($language)) {
      $term_name = $entity->label();
    }

    return $term_name;
  }

}
