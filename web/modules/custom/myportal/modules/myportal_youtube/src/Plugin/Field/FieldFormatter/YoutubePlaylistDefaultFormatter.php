<?php

namespace Drupal\myportal_youtube\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'youtube_list_default' formatter.
 *
 * @FieldFormatter(
 *   id = "youtube_playlist_default_formatter",
 *   label = @Translation("Youtube playlist default formatter"),
 *   field_types = {
 *     "youtube_playlist_default"
 *   }
 * )
 */
class YoutubePlaylistDefaultFormatter extends FormatterBase {

  const YOUTUBE_THUMBNAIL_WIDTH = 500;
  const YOUTUBE_THUMBNAIL_HEIGHT = 281;
  const YOUTUBE_BASE_PATH = 'https://www.youtube.com/watch?v=';

  /**
   * YouTube service.
   *
   * @var \Drupal\myportal_youtube\YoutubeService
   */
  protected $myportalYoutube;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->myportalYoutube = $container->get('myportal_youtube');
    $instance->iFrameUrlHelper = $container->get('media.oembed.iframe_url_helper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [
      '#theme' => 'youtube_block_playlist',
      '#title' => NULL,
      '#more' => NULL,
      '#description' => NULL,
      '#items' => [],
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];

    $playlist = NULL;
    $playlist_id = $items->getValue()[0]['playlist_id'];
    $total_items = (int) $items->getValue()[0]['total_items'];

    if (!empty($playlist_id)) {
      $playlist = $this->myportalYoutube->getPlaylistById($playlist_id);
    }

    if (empty($playlist_id)) {
      return [];
    }

    $item_count = 0;
    $video_items = $this->myportalYoutube->getPlaylistItemsByPlaylistId($playlist->id);

    foreach ($video_items as $key => $asd) {
      if (is_object($asd->snippet) && !empty((array) $asd->snippet->thumbnails)) {
        continue;
      }
      else {
        unset($video_items[$key]);
      }
    }

    foreach ($video_items as $delta => $item) {
      if ($item_count == $total_items) {
        break;
      }

      $elements['#items'][$delta] = $this->viewElement($item);
      $item_count++;
    }

    $elements['#title'] = $playlist->snippet->title;
    $elements['#description'] = $playlist->snippet->description;

    $elements['#attached']['library'][] = 'myportal_youtube/myportal_youtube';

    if ($item_count < count($video_items)) {
      $elements['#more'] = [
        'url' => 'https://www.youtube.com/playlist?list=' . $playlist_id,
        'title' => $this->t('See more on YouTube'),
      ];
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single zone item.
   *
   * @param mixed $item
   *   Video item.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement($item): array {
    $video_path = self::YOUTUBE_BASE_PATH . $item->contentDetails->videoId;
    $max_width = self::YOUTUBE_THUMBNAIL_WIDTH;
    $max_height = self::YOUTUBE_THUMBNAIL_HEIGHT;

    $url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => $video_path,
        'max_width' => $max_width,
        'max_height' => $max_height,
        'hash' => $this->iFrameUrlHelper->getHash($video_path, $max_width, $max_height),
      ],
    ]);

    // Render videos and rich content in an iframe for security reasons.
    // @see: https://oembed.com/#section3
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $url->toString(),
        'frameborder' => 0,
        'scrolling' => FALSE,
        'allowtransparency' => TRUE,
        'loading' => 'lazy',
        'width' => $max_width,
        'height' => $max_height,
        'class' => [
          'media-oembed-content',
          'responsive-video',
        ],
        'style' => 'width: ' . $max_width . 'px; height: ' . $max_height . 'px;',
      ],
      '#attached' => [
        'library' => [
          'media/oembed.formatter',
        ],
      ],
    ];
  }

}
