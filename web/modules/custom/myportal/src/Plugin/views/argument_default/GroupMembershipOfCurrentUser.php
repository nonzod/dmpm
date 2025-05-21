<?php

namespace Drupal\myportal\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\myaccess\GroupManagerInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the GroupMembershipOfCurrentUser class.
 *
 * @ViewsArgumentDefault(
 *   id = "myportal_group_user",
 *   title = @Translation("Groups that current user is member")
 * )
 * @package Drupal\myportal\Plugin\views\argument_default
 */
class GroupMembershipOfCurrentUser extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Group manager service.
   *
   * @var \Drupal\myaccess\GroupManagerInterface
   */
  private $groupManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountInterface);
    $instance->currentUser = $current_user;

    $group_manager = $container->get('myaccess.group_manager');
    assert($group_manager instanceof GroupManagerInterface);
    $instance->groupManager = $group_manager;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $group_ids = $this->groupManager->getGroupIdsByUser($this->currentUser);
    $ids = [];
    foreach ($group_ids as $group_id) {
      $ids[] = $group_id;
    }

    // Return a multiple values in 'OR' condition logic.
    return implode('+', $ids);
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
    $groups = $this->groupManager->getGroupIdsByUser($this->currentUser);
    $tags = ['user:' . $this->currentUser->id()];
    foreach ($groups as $group) {
      $tags[] = 'group:' . $group;
    }

    return $tags;
  }

}
