<?php

namespace Drupal\myportal_user\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\myportal_user\TransferOwnershipContentsBatch;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the TransferOwnershipContentsForm class.
 *
 * @package Drupal\myportal_user\Form
 */
class TransferOwnershipContentsForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The submitted data needing to be confirmed.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $instance->entityTypeManager = $entity_type_manager;

    $current_user = $container->get('current_user');
    assert($current_user instanceof AccountInterface);
    $instance->currentUser = $current_user;

    $connection = $container->get('database');
    assert($connection instanceof Connection);
    $instance->connection = $connection;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_user_transfer_ownership_contents_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // When this is the confirmation step fall through to the confirmation form.
    if ($this->data) {

      foreach ($this->data['entities_to_transfer'] as $entities_type => $entities_data) {
        $storage = $this->entityTypeManager->getStorage($entities_type);
        $items = [];
        foreach ($entities_data as $langcode => $entities_id) {
          foreach ($entities_id as $entity_id) {

            // Load entity.
            $entity = $storage->load($entity_id);

            if ($entity instanceof TranslatableInterface
              && $entity->hasTranslation($langcode)) {
              // Load translation if exists.
              $entity = $entity->getTranslation($langcode);
            }

            if ($entity) {
              $items[] = "({$entity->language()->getName()}) {$entity->label()}";
            }
          }
        }
        $form[$entities_type] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t('List entities of type %type to transfer', ['%type' => $entities_type]),
          '#items' => $items,
          '#wrapper_attributes' => ['class' => 'container'],
        ];

      }

      return parent::buildForm($form, $form_state);
    }

    $form['user_from'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('From user'),
      '#description' => $this->t('User from whom you want to transfer the contents.'),
      '#target_type' => 'user',
      '#selection_handler' => 'default',
      '#selection_setttings' => [
        'include_anonymous' => FALSE,
      ],
      // Validation is done in static::validateConfigurationForm().
      '#validate_reference' => FALSE,
      '#size' => '6',
      '#maxlength' => '60',
      '#required' => TRUE,
    ];
    $form['user_to'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('To user'),
      '#description' => $this->t('User whose contents you want to transfer.'),
      '#target_type' => 'user',
      '#selection_handler' => 'default',
      '#selection_setttings' => [
        'include_anonymous' => FALSE,
      ],
      // Validation is done in static::validateConfigurationForm().
      '#validate_reference' => FALSE,
      '#size' => '6',
      '#maxlength' => '60',
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Transfer'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The confirmation step needs no additional validation.
    if ($this->data) {
      return;
    }

    foreach (['user_from', 'user_to'] as $field) {
      $exists = (bool) $this->connection
        ->queryRange('SELECT 1 FROM {users_field_data} WHERE uid = :uid AND default_langcode = 1', 0, 1, [':uid' => $form_state->getValue($field)])
        ->fetchField();

      if (!$exists) {
        $form_state->setErrorByName($field, $this->t('Enter a valid username.'));

        return;
      }
    }

    /** @var \Drupal\user\UserInterface $owner */
    $owner = $this->entityTypeManager
      ->getStorage('user')
      ->load($form_state->getValue('user_from'));
    assert($owner instanceof UserInterface);
    $entities = [];

    // Load contents id of user_from.
    // @todo add media and files.
    $entities['node'] = $this->getNodeTranslationByOwner($owner);

    $entities = array_filter($entities);
    if (empty($entities)) {
      $form_state->setErrorByName('user_from', $this->t("Not found entities to process."));
    }

    // Store data.
    $form_state->set('owner_uid', $form_state->getValue('user_to'));
    $form_state->set('entities_to_transfer', $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If this form has not yet been confirmed, store the values and rebuild.
    if (!$this->data) {
      $form_state->setRebuild();
      $this->data = $form_state->getValues();
      $this->data['owner_uid'] = $form_state->get('owner_uid');
      $this->data['entities_to_transfer'] = $form_state->get('entities_to_transfer');

      return;
    }

    // Init data.
    $owner_uid = $form_state->get('owner_uid');
    $entities_to_transfer = $form_state->get('entities_to_transfer');

    $this->batchTransfer($entities_to_transfer, $owner_uid);
  }

  /**
   * {@inheritDoc}
   */
  public function getQuestion() {
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');

    $user_from = $user_storage->load($this->data['user_from']);
    assert($user_from instanceof UserInterface);

    $user_to = $user_storage->load($this->data['user_to']);
    assert($user_to instanceof UserInterface);

    $args = [
      '%user_from' => $user_from->label(),
      '%user_to' => $user_to->label(),
    ];

    return $this->t('Are you sure you want to transfer the contents of %user_from to %user_to?', $args);
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl() {
    return new Url('myportal_user.transfer_ownership_contents_form');
  }

  /**
   * Init batch transfer contents.
   *
   * @param array $entities_to_transfer
   *   The entities id to transfer.
   * @param string $user_id
   *   The new owner.
   */
  protected function batchTransfer(array $entities_to_transfer, string $user_id) {
    $batch_builder = (new BatchBuilder())
      ->setTitle('Process entities transferring')
      ->setFinishCallback([
        TransferOwnershipContentsBatch::class,
        'finishBatch',
      ]);

    foreach ($entities_to_transfer as $entities_type => $entities_data) {
      foreach ($entities_data as $langcode => $entities_id) {

        // Process single item for single operation.
        /* foreach ($entities_id as $id) {
         *  $batch_builder->addOperation([
         *    TransferOwnershipContentsBatch::class,
         *    'processSingleItemBatch',
         *  ], [$id, $entity_type, $user]);
         * }
         */

        // Process all items in single operation.
        $batch_builder
          ->addOperation([
            TransferOwnershipContentsBatch::class,
            'processBatch',
          ], [$entities_id, $entities_type, $langcode, $user_id]);
      }
    }

    batch_set($batch_builder->toArray());
  }

  /**
   * Retrieve the node translations by owner.
   *
   * @param \Drupal\user\UserInterface $owner
   *   The owner of content.
   *
   * @return array
   *   An array of object contains the node id grouped by language.
   */
  protected function getNodeTranslationByOwner(UserInterface $owner) {
    // We can't use the NodeStorageQuery because not return the language.
    // Also the loadByProperties doesn't work because filter by default lang.
    $results = $this->connection
      ->select('node_field_data', 'n')
      ->fields('n', ['nid', 'langcode'])
      ->condition('uid', (string) $owner->id())
      ->execute();
    $records = $results instanceof StatementInterface ? $results->fetchAll() : [];

    $entities = [];
    foreach ($records as $record) {
      $entities[$record->langcode][] = $record->nid;
    }

    return $entities;
  }

}
