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
 * Configure welcome message for all users.
 */
class WelcomeMessageForm extends ConfigFormBase {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WelcomeMessageForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  final public function __construct(ConfigFactoryInterface $config_factory,
                              StateInterface $state,
                              LanguageManagerInterface $language_manager,
                              MessengerInterface $messenger,
                              EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    $config_factory = $container->get('config.factory');
    assert($config_factory instanceof ConfigFactoryInterface);

    $state = $container->get('state');
    assert($state instanceof StateInterface);

    $language_manager = $container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);

    $messenger = $container->get('messenger');
    assert($messenger instanceof MessengerInterface);

    $entity_type_manager = $container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    return new static(
      $config_factory,
      $state,
      $language_manager,
      $messenger,
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'myportal.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach ($this->languageManager->getLanguages() as $language) {
      $lang_code = $language->getId();

      $form["myp_welcome_message_{$lang_code}"] = [
        '#type' => 'textfield',
        '#default_value' => $this->state->get("myp_welcome_message_{$lang_code}"),
        '#title' => $this->t("Welcome Message @lang:", ["@lang" => $lang_code]),
        '#description' => $this->t('Write welcome message for all users, Maximum of 40 characters.'),
        '#attributes' => [
          'maxlength' => 40,
          'size' => 40,
        ],
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->languageManager->getLanguages() as $language) {
      $lang_code = $language->getId();
      $this->state->set("myp_welcome_message_{$lang_code}", $form_state->getValue("myp_welcome_message_{$lang_code}"));
    }
    Cache::invalidateTags(['myp:welcome:message']);

    $this->messenger->addMessage('Saved successfully!');
    parent::submitForm($form, $form_state);
  }

}
