<?php

declare(strict_types = 1);

namespace Drupal\myportal_news\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the SettingsForm class.
 *
 * @package Drupal\myportal_news\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Group Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupStorage;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Construct new SettingsForm instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  final public function __construct(
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
    $this->groupStorage = $entity_type_manager->getStorage('group');
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config_factory = $container->get('config.factory');
    assert($config_factory instanceof ConfigFactoryInterface);

    $language_manager = $container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);

    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    $date_formatter = $container->get('date.formatter');
    assert($date_formatter instanceof DateFormatterInterface);

    return new static(
      $config_factory,
      $language_manager,
      $entity_type_manager,
      $date_formatter
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'myportal_news.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_news_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('myportal_news.settings');

    $languages = $this->languageManager->getLanguages();
    $header_language = array_map(function ($value) {
      return $this->t('Source for %language_name', ['%language_name' => $value->getName()]);
    }, $languages);

    $form['feeds'] = [
      '#type' => 'table',
      '#title' => 'Sample Table',
      '#header' => [$this->t('Group')] + $header_language,
    ];

    $groups_id = $this->groupStorage->getQuery()
      ->condition('field_group_scope', 'country')
      ->sort('label')
      ->execute();

    if (!empty($groups_id) && is_array($groups_id)) {
      /** @var \Drupal\group\Entity\GroupInterface[] $groups */
      $groups = $this->groupStorage->loadMultiple($groups_id);
    }
    else {
      $groups = [];
    }

    $feeds = $config->get('feeds');

    foreach ($groups as $group) {
      $group_id = (string) $group->id();
      $form['feeds'][$group_id] = [];
      $form['feeds'][$group_id]['none'] = [
        '#type' => 'item',
        '#markup' => $group->label(),
      ];
      foreach ($languages as $language) {
        $language_id = $language->getId();
        $form['feeds'][$group_id][$language_id] = [
          '#type' => 'url',
          '#title' => $this->t('Source Url'),
          '#title_display' => 'invisible',
          '#default_value' => $feeds[$group_id][$language_id] ?? NULL,
          '#placeholder' => 'https://provider-news.it/json-api/...',
        ];
      }
    }

    $period = [
      0,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $period = array_map([
      $this->dateFormatter,
      'formatInterval',
    ], array_combine($period, $period));
    $period[0] = '<' . $this->t('no caching') . '>';
    $form['cache_maximum_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache maximum age'),
      '#default_value' => $config->get('cache_maximum_age'),
      '#options' => $period,
      '#description' => $this->t('The maximum age for which news is cached before checking for new updates.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $feeds = array_filter($form_state->getValue(['feeds']));
    foreach ($feeds as $group_id => $config) {
      if (empty(array_filter($config))) {
        continue;
      }
      if (empty($config['en'])) {
        $form_state->setErrorByName("feeds][{$group_id}][en", $this->t('Source Url is required.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $feeds = array_filter($form_state->getValue(['feeds']));
    foreach ($feeds as &$config) {
      unset($config['none']);
    }

    $this->config('myportal_news.settings')
      ->set('cache_maximum_age', $form_state->getValue(['cache_maximum_age']))
      ->set('feeds', $feeds)
      ->save();
  }

}
