<?php

namespace Drupal\myportal_localadmin\Plugin\facets\processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets the favorite legal entity value from user profile as facet default.
 *
 * @FacetsProcessor(
 *   id = "favorite_profile_value_processor",
 *   label = @Translation("Favorite value in profile"),
 *   description = @Translation("Sets user's favorite legal entity value from profile as default when facet is loaded."),
 *   stages = {
 *     "pre_query" = 30
 *   }
 * )
 */
class FavoriteProfileValueProcessor extends ProcessorPluginBase implements PreQueryProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new processor instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    // Only apply favorite value if no active items already exist
    if (empty($facet->getActiveItems())) {
      // Load the current user entity to get the profile field
      $user = User::load($this->currentUser->id());
      
      // Check if the user has a favorite legal entity set
      if ($user && $user->hasField('favourite_legalentity') && !$user->get('favourite_legalentity')->isEmpty()) {
        $favorite_entity = $user->get('favourite_legalentity')->value;
        
        // Only set the value if it's not empty
        if (!empty($favorite_entity)) {
          $facet->setActiveItem($favorite_entity);
        }
      }
    }
  }

  public function build(FacetInterface $facet, array $results) {
    // Load the current user entity to get the profile field
    $user = User::load($this->currentUser->id());
    
    // Check if the user has a favorite legal entity set
    if ($user && $user->hasField('favourite_legalentity') && !$user->get('favourite_legalentity')->isEmpty()) {
      return null;
    }
    
    return $results;
  }
}