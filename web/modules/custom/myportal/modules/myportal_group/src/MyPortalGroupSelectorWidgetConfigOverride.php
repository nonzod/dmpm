<?php

namespace Drupal\myportal_group;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Implements custom configuration override.
 */
class MyPortalGroupSelectorWidgetConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * MyPortalGroupSelectorWidgetConfigOverride constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_names = [
      'core.entity_form_display.node.page.default',
      'core.entity_form_display.node.event.default',
      'core.entity_form_display.node.topic.default',
      'core.entity_form_display.application.mylinks.default',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $config = $this->configFactory->getEditable($config_name);
        // Add the field to the content.
        $content = $config->get('content');
        $content['groups'] = [];
        $content['groups']['type'] = 'myportal_group_selector_widget';
        $content['groups']['settings'] = [];
        $content['groups']['weight'] = 16;
        $content['groups']['region'] = 'content';
        $content['groups']['third_party_settings'] = [];

        $overrides[$config_name] = [
          'content' => $content,
        ];

      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'MyPortalGroupSelectorWidgetConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return $this->configFactory->getEditable($name);
  }

}
