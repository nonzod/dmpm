<?php

namespace Drupal\myportal\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the NavigationSectionOfCurrentUserEditor class.
 *
 * @ViewsArgumentDefault(
 *   id = "myportal_navigation_section_editor_user",
 *   title = @Translation("Navigation section ID that current user is editor")
 * )
 * @package Drupal\myportal\Plugin\views\argument_default
 */
class NavigationSectionOfCurrentUserEditor extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $term_storage = $container->get('entity_type.manager')
      ->getStorage('taxonomy_term');
    assert($term_storage instanceof TermStorageInterface);
    $instance->termStorage = $term_storage;

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountInterface);
    $instance->currentUser = $current_user;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $ids = $this->getUserEditorSectionIds($this->currentUser);

    // Return a multiple values in 'OR' condition logic.
    return is_array($ids) ? implode('+', $ids) : NULL;
  }

  /**
   * Retrieve all term IDs of navigation section that user is editor.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user interested.
   *
   * @return array|int
   *   An array of terms id.
   */
  protected function getUserEditorSectionIds(AccountInterface $user) {
    return $this->termStorage->getQuery()
      ->condition('vid', 'navigation')
      ->condition('field_navigation_editors', $user->id(), 'CONTAINS')
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $term_ids = $this->getUserEditorSectionIds($this->currentUser);
    $tags = ['user:' . $this->currentUser->id()];
    foreach ($term_ids as $term_id) {
      $tags[] = 'taxonomy_term:' . $term_id;
    }

    return $tags;
  }

}
