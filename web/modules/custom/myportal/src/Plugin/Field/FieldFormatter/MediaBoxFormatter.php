<?php

namespace Drupal\myportal\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'myportal_media_box_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "myp_media_box_formatter",
 *   label = @Translation("Media box formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaBoxFormatter extends EntityReferenceEntityFormatter {

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   *
   * @psalm-suppress PropertyTypeCoercion
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityRepository = $container->get('entity.repository');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $paragraph = $item->getEntity();

      if ($paragraph instanceof Paragraph) {
        $parent_uuid = $paragraph->getBehaviorSetting('layout_paragraphs', 'parent_uuid');
        if (empty($parent_uuid)) {
          return parent::viewElements($items, $langcode);
        }

        $image_style_name = $this->getImageStyle($parent_uuid);

        try {
          $media_id = $item->getEntity()->get('field_media')->getString();
          /** @var \Drupal\media\MediaInterface $media */
          $media = $this->entityTypeManager->getStorage('media')
            ->load($media_id);
        }
        catch (\Exception $exception) {
          $this->loggerFactory->get('myportal')
            ->error('MediaBoxFormatter throw exception: @message.', ['@message' => $exception->getMessage()]);

          continue;
        }

        if ($media == NULL) {
          return $elements;
        }

        if ($media->bundle() !== 'image') {
          return parent::viewElements($items, $langcode);
        }

        try {
          $file_id = $media->get('field_media_image')->__get('target_id');
          /** @var \Drupal\file\FileInterface $file */
          $file = $this->entityTypeManager->getStorage('file')->load($file_id);
        }
        catch (\Exception $exception) {
          $this->loggerFactory->get('myportal')
            ->error('MediaBoxFormatter throw exception: @message.', ['@message' => $exception->getMessage()]);

          continue;
        }

        if ($file == NULL) {
          return $elements;
        }

        // Build render array.
        $elements[$delta] = [
          '#theme' => 'image_style',
          '#style_name' => $image_style_name,
          '#uri' => $file->getFileUri(),
          '#alt' => $media->getName(),
          '#title' => $media->getName(),
        ];
      }
    }

    return $elements;
  }

  /**
   * Retrieves suitable image style by parent paragraph uuid.
   *
   * @param string $paragraph_uuid
   *   Paragraph UUID.
   *
   * @return string
   *   Image style ID.
   */
  protected function getImageStyle(string $paragraph_uuid): string {
    $mapping = [
      'layout_onecol' => 'widget_100',
      'layout_twocol' => 'widget_50',
      'layout_twocol_50_50' => 'widget_50',
      'layout_twocol_70_30' => 'widget_30',
      'layout_twocol_30_70' => 'widget_30',
      'layout_threecol' => 'widget_30',
    ];
    $layout = 'layout_onecol';

    try {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $this->entityRepository->loadEntityByUuid('paragraph', $paragraph_uuid);

      if (!$paragraph instanceof ParagraphInterface) {
        return 'widget_100';
      }
      $layout = $paragraph->getBehaviorSetting('layout_paragraphs', 'layout');
    }
    catch (EntityStorageException $exception) {
      $this->loggerFactory->get('myportal')
        ->error('No paragraph found with uuid @uuid. [@trace]', [
          '@uuid' => $paragraph_uuid,
          '@trace' => $exception->getMessage(),
        ]);
    }

    return $mapping[$layout] ?? 'widget_100';
  }

}
