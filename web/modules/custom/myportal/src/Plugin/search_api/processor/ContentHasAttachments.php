<?php

namespace Drupal\myportal\Plugin\search_api\processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Defines the ContentHasAttachments class.
 *
 * @SearchApiProcessor(
 *   id = "myportal_content_has_attachments",
 *   label = @Translation("Content Has Attachments"),
 *   description = @Translation("Store if content has attachments."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 *
 * @package Drupal\myportal\Plugin\search_api\processor
 */
class ContentHasAttachments extends ProcessorPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Content Has Attachments'),
        'description' => $this->t('Store if content has attachments.'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['myportal_content_has_attachments'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function addFieldValues(ItemInterface $item) {
    $has_attachments = FALSE;
    $original_object = $item->getOriginalObject();
    if (empty($original_object)) {
      return;
    }
    $node = $original_object->getValue();

    if (!$node instanceof NodeInterface) {
      // Apparently we were active for a wrong item.
      return;
    }

    if ($node->hasField('field_files')
      && !$node->get('field_files')->isEmpty()) {
      $has_attachments = TRUE;
    }

    if ($node->hasField('field_contents')
      && $node->get('field_contents') instanceof EntityReferenceFieldItemListInterface) {
      $field = $node->get('field_contents');
      assert($field instanceof EntityReferenceFieldItemListInterface);

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      foreach ($field->referencedEntities() as $paragraph) {
        if ($paragraph instanceof ContentEntityInterface
          && $paragraph->hasField('field_files')
          && !$paragraph->get('field_files')->isEmpty()) {
          $has_attachments = TRUE;
        }
      }
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath(
        $item->getFields(),
        NULL,
        'myportal_content_has_attachments'
      );
    foreach ($fields as $field) {
      $field->addValue($has_attachments);
    }
  }

}
