<?php

namespace Drupal\myportal;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\ViewExecutableFactory;
use Psr\Log\LoggerInterface;
use Drupal\views\ViewExecutable;

/**
 * Class VisibleTaxonomy, control the number of content visible to the user.
 *
 * @package Drupal\myportal
 */
class VisibleTaxonomy implements VisibleTaxonomyInterface {

  const VIEWS_NAVIGATION = 'primary_navigation';

  const VIEWS_NAVIGATION_DISPLAY = 'page_1';

  /**
   * The Entity type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The factory to load a view executable with.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The wrapped cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * VisibleTaxonomy constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The views executable factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ViewExecutableFactory $executable_factory,
    LoggerInterface $logger,
    AccountInterface $account,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache_backend
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->executableFactory = $executable_factory;
    $this->logger = $logger;
    $this->currentUser = $account;
    $this->languageManager = $language_manager;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVisibleTaxonomy(int $term_id): bool {
    $cid = [];
    $cid[] = 'myportal';
    $cid[] = 'visible_taxonomy';
    // Required because the query was prepared between user.
    $cid[] = $this->currentUser->id();
    $cid[] = $term_id;
    $cid[] = $this->languageManager->getCurrentLanguage()->getId();
    $cid = implode(':', $cid);

    $data = FALSE;
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }
    else {
      try {
        $result = $this->executeSearchApiQuery($term_id);
        $data = is_array($result) && count($result) > 0;

        $tags = [
          'user:' . $this->currentUser->id(),
          'taxonomy_term:' . $term_id,
        ];
        $this->cacheBackend->set($cid, $data, time() + 3600, $tags);
      }
      catch (\Exception $e) {
        $this->logger->error('VisibleTaxonomy service throw exception: @message.', ['@message' => $e->getMessage()]);
      }
    }

    return $data;
  }

  /**
   * Search through custom views query the content for tid and current user.
   *
   * @param int $term_id
   *   Taxonomy term Id.
   *
   * @return \Drupal\views\ResultRow[]|false
   *   The result found from view.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @see \Drupal\myportal_group\EventSubscriber\SearchApiAlterSubscriber
   */
  protected function executeSearchApiQuery(int $term_id) {

    /** @var \Drupal\views\Entity\View $view_storage */
    $view_storage = $this->entityTypeManager
      ->getStorage('view')
      ->load(VisibleTaxonomy::VIEWS_NAVIGATION);

    $view = $this->executableFactory->get($view_storage);
    if (!$view instanceof ViewExecutable) {
      return FALSE;
    }
    $view->setArguments([$term_id]);
    $view->setDisplay(VisibleTaxonomy::VIEWS_NAVIGATION_DISPLAY);
    $view->execute();

    return $view->result;
  }

}
