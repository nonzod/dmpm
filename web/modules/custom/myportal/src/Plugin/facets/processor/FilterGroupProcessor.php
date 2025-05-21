<?php

namespace Drupal\myportal\Plugin\facets\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that hides results that don't match the configuration.
 *
 * @FacetsProcessor(
 *   id = "myp_filter_group_processor",
 *   label = @Translation("Filter Group Processor"),
 *   description = @Translation("Hides results that don't match the configuration"),
 *   stages = {
 *     "build" = 40
 *   }
 * )
 */
class FilterGroupProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FilterGroupProcessor constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $account,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->user = $account;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    $account = $container->get('current_user');
    assert($account instanceof AccountInterface);

    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $account,
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $processors = $facet->getProcessors();
    $config = $processors[$this->getPluginId()] ?? NULL;
    $build = [];
    $build['group_scope'] = [
      '#title' => $this->t('Groupe scope'),
      '#type' => 'select',
      '#default_value' => !is_null($config) ? $config->getConfiguration()['group_scope'] : '',
      '#options' => $this->getGroupScope(),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {

    $processors = $facet->getProcessors();
    $config = $processors[$this->getPluginId()];

    $groupe_scope = $config->getConfiguration()['group_scope'];

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $id => $result) {
      $gid = $result->getRawValue();

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = Group::load($gid);
      if ($group instanceof GroupInterface) {
        $scope = $group->get('field_group_scope')->getString();
        // If the user is not member remove  group in list.
        if ($scope !== $groupe_scope || !$group->getMember($this->user)) {
          unset($results[$id]);
        }
      }
    }

    return $results;
  }

  /**
   * Get the list allowed options for field, field_group_scope.
   *
   * @return array
   *   Return the array of items value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getGroupScope(): array {
    /** @var \Drupal\group\Entity\GroupInterface $groups */
    $groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    $possibleOptions = [];
    foreach ($groups as $group) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $groupScopeField */
      $groupScopeField = $group->get('field_group_scope');
      $optionsProvider = $groupScopeField->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('value', $groupScopeField->getEntity());
      $possibleOptions = ($optionsProvider !== NULL) ? $optionsProvider->getPossibleOptions() : [];
    }

    return $possibleOptions;
  }

}
