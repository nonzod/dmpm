<?php

namespace Drupal\myportal\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines the TechHelpForm class.
 *
 * @package Drupal\myportal\Form
 */
class TechHelpForm extends ConfigFormBase {

  /**
   * The language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WelcomeMessageForm constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_tech_help_link_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'myportal.tech_help.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('myportal.tech_help.settings');
    $form["tech_help_link_active"] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get("tech_help_link_active"),
      '#title' => $this->t("Is 'Tech Help link' active?"),
    ];
    $form["tech_help_data"] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tech Help link settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="tech_help_link_active"]' => ['checked' => TRUE],
        ]
      ],
    ];
    $form["tech_help_data"]["tech_help_link"] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#default_value' => $this->entityTypeManager->getStorage('node')->load($config->get("tech_help_link")),
      '#title' => $this->t("Tech Help link URL"),
      '#states' => [
        'required' => [
          ':input[name="tech_help_link_active"]' => ['checked' => TRUE],
        ]
      ],
      /*'#attributes' => [
        'maxlength' => 64,
        'size' => 64,
      ],*/
    ];
    foreach ($this->languageManager->getLanguages() as $language) {
      $lang_code = $language->getId();

      $form["tech_help_data"]["tech_help_label_{$lang_code}"] = [
        '#type' => 'textfield',
        '#default_value' => $config->get("tech_help_label_{$lang_code}"),
        '#title' => $this->t("Tech Help link label (@lang):", ["@lang" => $lang_code]),
        '#description' => $this->t('(max. 60 characters).'),
        '#attributes' => [
          'maxlength' => 64,
          'size' => 64,
        ],
      ];
    }


    /*$form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];*/

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('myportal.tech_help.settings');
    $config->set("tech_help_link", $form_state->getValue("tech_help_link"))
      ->set("tech_help_link_active", $form_state->getValue("tech_help_link_active"));
    foreach ($this->languageManager->getLanguages() as $language) {
      $lang_code = $language->getId();
      $config->set("tech_help_label_{$lang_code}", $form_state->getValue("tech_help_label_{$lang_code}"));
    }
    $config->save();

    Cache::invalidateTags(['myp:tech_help']);
    parent::submitForm($form, $form_state);
  }

}
