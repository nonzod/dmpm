<?php

declare(strict_types=1);

namespace Drupal\myaccess\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for add and edit a application entity.
 */
class ApplicationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\myaccess\Entity\Application $entity */
    $entity = $this->entity;
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Acronym'),
      '#default_value' => !empty($entity->get('description')->getString()) ? $entity->get('description')->getString() : '',
      '#description' => $this->t('Insert Acronym'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    $this->messenger()->addMessage(
      sprintf(
        '%s %s saved',
        $entity->getEntityType()->getLabel(), $entity->label() ?? ''
      )
    );

    $form_state->setRedirectUrl($this->getRedirectUrl());

    return $status;
  }

  /**
   * Returns the URL where the user should be redirected after creation.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    $entity = $this->getEntity();
    if ($entity->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $entity->toUrl('collection');
    }
    else {
      // Otherwise fall back to the front page.
      return Url::fromRoute('<front>');
    }
  }

}
