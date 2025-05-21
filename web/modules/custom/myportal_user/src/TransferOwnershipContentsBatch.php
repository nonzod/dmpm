<?php

namespace Drupal\myportal_user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the TransferOwnershipContentsBatch class.
 *
 * @package Drupal\myportal_user
 */
class TransferOwnershipContentsBatch {

  const ITEMS_FOR_BATCH = 5;

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Indicates whether the batch has completed successfully.
   * @param array $results
   *   The array of results gathered by the batch processing.
   * @param string[] $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    // Extract errors information from results (used in processBatch()).
    $results_error = [];
    if (isset($results['errors'])) {
      $results_error = $results['errors'];
      unset($results['errors']);
    }

    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of items we processed (and errors).
      $messenger->addMessage(t('@count results processed successful.', ['@count' => count($results)]));
      if (!empty($results_error)) {
        $messenger->addWarning(t('@count results processed with error.', ['@count' => count($results_error)]));
        foreach ($results_error as $result_error) {
          $messenger->addWarning($result_error);
        }
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * Run action to transfer the ownership.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\ContentEntityInterface $content
   *   The entity to change ownership.
   * @param \Drupal\user\UserInterface $new_owner
   *   The user to assign the content.
   *
   * @return bool
   *   Return true if operation not have an error.
   */
  public static function action(EntityInterface $content, UserInterface $new_owner) {
    if (!$content instanceof EntityOwnerInterface) {
      throw new \InvalidArgumentException("This content not extended the EntityOwnerInterface.");
    }
    $content->setOwner($new_owner)->save();

    return TRUE;
  }

  /**
   * Processes the batch.
   *
   * @param array $contents_id
   *   The entities id.
   * @param string $content_type
   *   The entity type.
   * @param string|null $langcode
   *   The language code.
   * @param string $uid
   *   The user id to assign the content.
   * @param array $context
   *   The batch context information.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function processBatch(array $contents_id, string $content_type, ?string $langcode, string $uid, array &$context) {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($contents_id);
    }

    // Retrieve the contents id to process.
    $ids_to_process = array_slice($contents_id, $context['sandbox']['progress'], self::ITEMS_FOR_BATCH);

    // Load User.
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->load($uid);

    // Load nodes.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $content_storage */
    $content_storage = \Drupal::entityTypeManager()->getStorage($content_type);
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $contents */
    $contents = [];

    foreach ($content_storage->loadMultiple($ids_to_process) as $content) {
      if ($content instanceof TranslatableInterface
        && $langcode && $content->hasTranslation($langcode)) {
        $contents[] = $content->getTranslation($langcode);
      }
      else {
        $contents[] = $content;
      }
    }

    foreach ($contents as $content) {

      // Update our progress information.
      $context['sandbox']['progress']++;
      $context['message'] = t('Now processing %current_content', ['%current_content' => $content->label()]);

      // Index.
      try {
        self::action($content, $user);
        $context['results'][] = $content->id();
      }
      catch (\Throwable $exception) {
        $context['results']['errors'][] = "{$content->id()}: {$exception->getMessage()}";
      }
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = ($context['sandbox']['progress'] >= $context['sandbox']['max']);
    }
  }

  /**
   * Processes the batch (single item mode).
   *
   * @param string $content_id
   *   The entity id.
   * @param string $content_type
   *   The entity type.
   * @param string|null $langcode
   *   The language code.
   * @param string $uid
   *   The user id to assign the content.
   * @param array $context
   *   The batch context information.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function processSingleItemBatch(string $content_id, string $content_type, ?string $langcode, string $uid, array &$context) {

    // Load node.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($content_type);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $content */
    $content = $storage->load($content_id);

    if ($content instanceof TranslatableInterface
      && $langcode && $content->hasTranslation($langcode)) {

      // Retrieve the correct translation to operate.
      $content = $content->getTranslation($langcode);
    }

    // Load User.
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->load($uid);

    // Run action.
    self::action($content, $user);

    // Store some results for post-processing in the 'finished' callback.
    // The contents of 'results' will be available as $results in the
    // 'finished' function.
    $context['results'][] = $content->id();

    // Optional message displayed under the progressbar.
    $context['message'] = t('Running action to content "@label"',
      ['@label' => $content->label()]
    );
  }

}
