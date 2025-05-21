<?php

namespace Drupal\myportal_youtube\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'zone' field type.
 *
 * @FieldType(
 *   id = "youtube_playlist_default",
 *   label = @Translation("Youtube playlist"),
 *   description = @Translation(""),
 *   default_formatter = "youtube_playlist_default_formatter",
 *   default_widget = "youtube_playlist_default_widget",
 *   cardinality = 1,
 * )
 */
class YoutubePlaylist extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'playlist_id' => [
          'description' => 'Playlist ID.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'total_items' => [
          'description' => 'Serialized array of options for the link.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['playlist_id'] = DataDefinition::create('string')
      ->setLabel(t('playlist id'));

    $properties['total_items'] = DataDefinition::create('integer')
      ->setLabel(t('total'));

    return $properties;
  }

  /**
   * Define when the field type is empty.
   *
   * This method is important and used internally by Drupal. Take a moment
   * to define when the field fype must be considered empty.
   */
  public function isEmpty(): bool {
    return empty($this->get('playlist_id')->getValue()) && empty($this->get('total_items')->getValue());
  }

}
