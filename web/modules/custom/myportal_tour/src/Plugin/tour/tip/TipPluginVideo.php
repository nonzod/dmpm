<?php

namespace Drupal\myportal_tour\Plugin\tour\tip;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\tour\TipPluginBase;
use Drupal\tour\TourTipPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays some video as a tip.
 *
 * @Tip(
 *   id = "myp_video",
 *   title = @Translation("Video")
 * )
 */
class TipPluginVideo extends TipPluginBase implements ContainerFactoryPluginInterface, TourTipPluginInterface {

  /**
   * The video text which is used for render of this Video Tip.
   *
   * @var string
   */
  protected string $video;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The forced position of where the tip will be located.
   *
   * @var string
   */
  protected string $location;

  /**
   * Unique aria-id.
   *
   * @var string
   */
  protected string $ariaId;

  /**
   * Constructs a \Drupal\tour\Plugin\tour\tip\TipPluginVideo object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $token = $container->get('token');
    assert($token instanceof Token);

    return new static($configuration, $plugin_id, $plugin_definition, $token);
  }

  /**
   * Returns a ID that is guaranteed uniqueness.
   *
   * @return string
   *   A unique id to be used to generate aria attributes.
   */
  public function getAriaId(): string {
    if (!$this->ariaId) {
      $this->ariaId = Html::getUniqueId($this->get('id'));
    }

    return $this->ariaId;
  }

  /**
   * Returns location of the text tip.
   *
   * @return string
   *   The tip location.
   */
  public function getLocation(): ?string {
    return $this->get('location');
  }

  /**
   * Return the iframe video embed.
   *
   * @return string
   *   The iframe of embed.
   */
  public function getVideo(): string {
    return $this->get('video');
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    $attributes = parent::getAttributes();
    $attributes['data-aria-describedby'] = 'tour-tip-' . $this->getAriaId() . '-contents';
    $attributes['data-aria-labelledby'] = 'tour-tip-' . $this->getAriaId() . '-label';
    if ($location = $this->get('location')) {
      $attributes['data-options'] = 'tipLocation:' . $location;
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(): array {
    $output = '';
    if (!empty($this->getVideo())) {
      $output .= '<p class="tour-tip-video">' . $this->getVideo() . '</p>';
    }

    return [
      '#markup' => $output,
      '#allowed_tags' => ['iframe', 'p'],
    ];
  }

}
