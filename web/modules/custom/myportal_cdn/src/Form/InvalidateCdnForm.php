<?php

declare(strict_types=1);

namespace Drupal\myportal_cdn\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\myportal_cdn\ComputeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to invalidate all cdn cache.
 */
class InvalidateCdnForm extends FormBase {

  /**
   * The Google Compute service.
   *
   * @var \Drupal\myportal_cdn\ComputeService
   */
  private $computeService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): InvalidateCdnForm {
    $instance = parent::create($container);

    $compute_service = $container->get('myportal_cdn.compute_service');
    assert($compute_service instanceof ComputeService);
    $instance->computeService = $compute_service;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myportal_cdn.invalidate_cdn_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['clear_cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Clear cache'),
      '#open' => TRUE,
    ];

    $form['clear_cache']['help'] = [
      '#markup' => '<div>' . $this->t('Hit the button to send a request to the cdn to invalidates all of its cache. Depending on the size of the cache this may take some time to conclude.') . '</div>',
    ];

    $form['clear_cache']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all caches'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->computeService->invalidate('/*');

    $this->messenger()->addStatus($this->t('Cdn cache clear request sent, this may take some time to complete.'));
  }

}
