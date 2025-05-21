<?php

namespace Drupal\myportal_youtube\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'youtube_playlist_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "youtube_playlist_default_widget",
 *   label = @Translation("Yotube list widget"),
 *   field_types = {
 *     "youtube_playlist_default"
 *   },
 * )
 */
class YoutubePlaylistDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['playlist_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Playlist ID'),
      '#default_value' => isset($items[$delta]->playlist_id) ? $items[$delta]->playlist_id : '',
    ];

    $element['total_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Total Items'),
      '#default_value' => $items[$delta]->total_items,
    ];

    return $element;
  }

}
