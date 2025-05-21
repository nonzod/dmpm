<?php

declare(strict_types=1);

namespace Drupal\odv\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\odv\CompaniesManagerInterface;
use Drupal\odv\DTO\Submission;
use Drupal\odv\Event\ODVEvents;
use Drupal\odv\Event\ODVSubmitEvent;
use Drupal\odv\ReceiptGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to add ODV submissions.
 */
class SubmissionForm extends FormBase {

  /**
   * Companies manager service.
   *
   * Must be protected to be accessible in the ajax callback.
   *
   * @var \Drupal\odv\CompaniesManagerInterface
   */
  protected CompaniesManagerInterface $companiesManager;

  /**
   * The Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The Zip generator service.
   *
   * @var \Drupal\odv\PdfZipReceiptGenerator|\Drupal\odv\ReceiptGeneratorInterface
   */
  protected ReceiptGeneratorInterface $zipGenerator;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odv.submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $companies_manager = $container->get('odv.companies_manager');
    assert($companies_manager instanceof CompaniesManagerInterface);
    $instance->companiesManager = $companies_manager;

    $event_dispatcher = $container->get('event_dispatcher');
    assert($event_dispatcher instanceof EventDispatcherInterface);
    $instance->eventDispatcher = $event_dispatcher;

    $zip_generator = $container->get('odv.zip_generator');
    assert($zip_generator instanceof ReceiptGeneratorInterface);
    $instance->zipGenerator = $zip_generator;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('odv.settings');

    $form['help'] = [
      '#markup' => '<p>' . $settings->get('disclaimer') . '</p>',
    ];

    $form['terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept'),
      '#required' => TRUE,
    ];

    $form['company'] = [
      '#type' => 'select',
      '#title' => $this->t('Company'),
      '#options' => $this->companiesManager->getCompanies(),
      '#empty_option' => $this->t('- Select company -'),
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#ajax' => [
        'event' => 'change',
        'callback' => '::companySelectCallback',
        'wrapper' => 'field-recipient',
      ],
    ];

    $company = $form_state->getValue('company');

    $form['recipient'] = [
      '#type' => 'select',
      '#title' => $this->t('Recipient'),
      '#options' => $this->companiesManager->getRecipientsForCompany($company),
      '#empty_option' => $this->t('- Select recipient -'),
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#states' => [
        '!visible' => [
          ':input[name="company"]' => ['value' => ''],
        ],
      ],
      '#prefix' => '<div id="field-recipient">',
      '#suffix' => '</div>',
    ];

    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#states' => [
        '!visible' => [
          ':input[name="recipient"]' => ['value' => ''],
        ],
      ],
    ];

    $form['message']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
    ];

    $form['message']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#required' => TRUE,
      '#rows' => 10,
    ];

    $form['message']['attachments'] = [
      '#type' => 'dropzonejs',
      '#title' => $this->t('Attachments'),
      '#dropzone_description' => $this->t('To attach a file drop it or click here'),
      '#max_filesize' => '32M',
      '#extensions' => $settings->get('allowed_extensions'),
      '#clientside_resize' => FALSE,
      '#save_path' => 'odv',
    ];

    $form['message']['anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Anonymous'),
    ];

    $form['message']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#attributes' => ['class' => ['btn-men btn-primary']],
    ];

    return $form;
  }

  /**
   * Ajax callback to return the updated recipient list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure of the recipient element.
   */
  public function companySelectCallback(
    array $form,
    FormStateInterface $form_state
  ): array {
    return $form['recipient'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $attachments = array_map(function (array $element): \SplFileInfo {
      return new \SplFileInfo($element['path']);
    }, $form_state->getValue('attachments')['uploaded_files']);

    $submission = new Submission(
      $form_state->getValue('company'),
      $form_state->getValue('recipient'),
      $form_state->getValue('subject'),
      $form_state->getValue('body'),
      $attachments,
      $form_state->getValue('anonymous') == 1,
      $form_state->getValue('terms') == 1,
      $this->currentUser()->getEmail() ?? 'noemail@example.com'
    );

    $this->eventDispatcher->dispatch(ODVEvents::SUBMIT,
      new ODVSubmitEvent($submission));

    try {
      $path = $this->zipGenerator->generate($submission);
      $form_state->setRedirect('odv.thank_you', ['id' => $path]);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error creating the request receipt.'));
      $this->logger('odv')->error($e->getMessage());
    }
  }

}
