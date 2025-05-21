<?php

namespace Drupal\myportal_localadmin\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupInterface;
use Drupal\myportal_localadmin\Tables\LocalAdminGroups;
use Drupal\myportal_localadmin\Tables\LocalAdminUsers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
/**
 * Controller for Local Admin area
 */
class LocalAdminController extends ControllerBase {

  /**
   * The Local Admin Groups service.
   *
   * @var \Drupal\myportal_localadmin\Tables\LocalAdminGroups
   */
  protected LocalAdminGroups $groupsService;

  /**
   * The Local Admin Users service.
   *
   * @var \Drupal\myportal_localadmin\Tables\LocalAdminUsers
   */
  protected LocalAdminUsers $usersService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->groupsService = $container->get('myportal_localadmin.groups_list'); 
    $instance->usersService = $container->get('myportal_localadmin.users_list');

    return $instance;
  }

  /**
   * Overview page
   */
  public function renderIndex() {
    $usersLink = Link::createFromRoute('Group Users Overview', 'myportal_localadmin.group_users_overview')->toString();
    $groupsLink = Link::createFromRoute('Groups Overview', 'myportal_localadmin.groups_overview')->toString();
    return [
      '#title' => "Local Admin",
      '#markup' =><<<EOF
        <ul>
          <li>$groupsLink</li>
          <li>$usersLink</li>
        </ul>
      EOF
    ];
  }

  /**
   * List users page
   */
  public function renderUsers() {
    return $this->usersService->getRenderableArray();
  }

  /**
   * List groups page
   */
  public function renderGroups() {
    return $this->groupsService->getRenderableArray();
  }

  /**
   * 
   * @param Drupal\myportal_localadmin\Controller\GroupInterface $group 
   * @param mixed $plugin_id 
   * @return array 
   * @throws ContainerNotInitializedException 
   * @throws ServiceCircularReferenceException 
   * @throws ServiceNotFoundException 
   * @throws PluginNotFoundException 
   * @throws InvalidPluginDefinitionException 
   */
  public function addGroupUser(GroupInterface $group, $plugin_id) {
    // Verifica che il plugin esista
    if ($plugin_id !== 'group_membership') {
      throw new PluginNotFoundException('Plugin non valido');
    }
    
    // Ottieni il tipo di contenuto corretto per il plugin
    $group_content_type = $this->entityTypeManager()
      ->getStorage('group_content_type')
      ->loadByProperties([
        'group_type' => $group->bundle(),
        'content_plugin' => $plugin_id,
      ]);
    
    if (empty($group_content_type)) {
      throw new PluginNotFoundException('Plugin non abilitato per questo tipo di gruppo');
    }
    
    // Prendi il primo elemento (dovrebbe essere solo uno)
    $group_content_type = reset($group_content_type);
    
    // Crea l'entità group_content con il bundle corretto
    $group_content = $this->entityTypeManager()
      ->getStorage('group_content')
      ->create([
        'type' => $group_content_type->id(),
        'gid' => $group->id(),
      ]);
    
    // Restituisci il form con l'entità precompilata
    return $this->entityFormBuilder()->getForm($group_content, 'add');
  }
}
