<?php

declare(strict_types=1);

namespace Drupal\odv\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for the ODV application.
 */
class ConfigurationForm extends ConfigFormBase {

  const KEY_NAME = 'name';

  const KEY_RECIPIENTS = 'recipients';

  /**
   * Email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  private EmailValidatorInterface $emailValidator;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['odv.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'odv.configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);

    $emailValidator = $container->get('email.validator');
    assert($emailValidator instanceof EmailValidatorInterface);
    $instance->emailValidator = $emailValidator;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $form['disclaimer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disclaimer'),
      '#required' => TRUE,
      '#default_value' => $this->config('odv.settings')->get('disclaimer'),
    ];

    $form['thank_you'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Thank you message'),
      '#required' => TRUE,
      '#default_value' => $this->config('odv.settings')->get('thank_you'),
    ];

    $form['email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email'),
    ];

    $form['email']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $this->config('odv.settings')->get('subject'),
    ];

    $form['email']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $this->config('odv.settings')->get('body'),
    ];

    $form['companies'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Companies and recipient emails'),
      '#description' => $this->t('Add one company for line, followed by its email address, pipe separated. Example: Company1|email1@example.com|email2@example.com'),
      '#required' => TRUE,
      '#default_value' => $this
        ->serializeCompanies(
          $this->config('odv.settings')->get('companies')
      ),
    ];

    $form['allowed_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed extensions'),
      '#description' => $this->t('A string of valid extensions separated by a space.'),
      '#required' => TRUE,
      '#default_value' => $this->config('odv.settings')->get('allowed_extensions'),
    ];

    $form['sender_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Sender email address'),
      '#description' => $this->t('Sender email address used when the request is anonymous.'),
      '#required' => TRUE,
      '#default_value' => $this->config('odv.settings')->get('sender_email'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $companies = $form_state->getValue('companies');
    $companies_deserialized = $this->deserializeCompanies($companies);

    // Validate email correctness.
    foreach ($companies_deserialized as $company) {
      foreach ($company[self::KEY_RECIPIENTS] as $recipient) {
        if (!$this->emailValidator->isValid($recipient)) {
          $form_state
            ->setErrorByName(
              'companies',
              $this->t(
                '%recipient is not a valida email address',
                ['%recipient' => $recipient]
              )
            );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $this->config('odv.settings')
      ->set('disclaimer', $form_state->getValue('disclaimer'));
    $this->config('odv.settings')
      ->set('thank_you', $form_state->getValue('thank_you'));
    $this->config('odv.settings')
      ->set(
        'companies',
        $this->deserializeCompanies($form_state->getValue('companies'))
      );
    $this->config('odv.settings')
      ->set('allowed_extensions', $form_state->getValue('allowed_extensions'));
    $this->config('odv.settings')
      ->set('sender_email', $form_state->getValue('sender_email'));

    $this->config('odv.settings')->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Convert companies from array (used in config) to string (used in form).
   *
   * From:
   *
   * Company1|email1@example.com|email2@example.com
   * Company2|email3@example.com|email4@example.com
   *
   * To:
   *
   *   -
   *     name:
   *       Company1
   *     recipients:
   *       - email1@example.com
   *       - email2@example.com
   *   -
   *     name:
   *       Company2
   *     recipients:
   *       - email3@example.com
   *       - email4@example.com
   *
   * @param array $companies
   *   Companies as array.
   *
   * @return string
   *   Companies as string.
   */
  private function serializeCompanies(array $companies): string {
    $companies_serialized = '';

    foreach ($companies as $company) {
      $companies_serialized .= $company[self::KEY_NAME];
      foreach ($company[self::KEY_RECIPIENTS] as $recipient) {
        $companies_serialized .= '|' . $recipient;
      }
      $companies_serialized .= "\n";
    }

    return $companies_serialized;
  }

  /**
   * Convert companies from string (used in form) to array (used in config).
   *
   * From:
   *
   *   -
   *     name:
   *       Company1
   *     recipients:
   *       - email1@example.com
   *       - email2@example.com
   *   -
   *     name:
   *       Company2
   *     recipients:
   *       - email3@example.com
   *       - email4@example.com
   *
   * To:
   *
   * Company1|email1@example.com|email2@example.com
   * Company2|email3@example.com|email4@example.com
   *
   * @param string $companies
   *   Companies as string.
   *
   * @return array
   *   Companies as array.
   */
  private function deserializeCompanies(string $companies): array {
    $companies_deserialized = [];

    $companies = trim($companies, "\r\n");
    foreach (explode("\r\n", $companies) as $company) {
      $data = explode('|', $company);

      // Company name is the first element.
      $name = array_shift($data);
      // All remaining are the recipients.
      $recipients = $data;

      $companies_deserialized[] = [
        self::KEY_NAME => $name,
        self::KEY_RECIPIENTS => $recipients,
      ];
    }

    return $companies_deserialized;
  }

}
