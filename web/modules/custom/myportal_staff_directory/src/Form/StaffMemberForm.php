<?php

namespace Drupal\myportal_staff_directory\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for creating/editing StaffMember entities.
 */
class StaffMemberForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Staff Member.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Staff Member.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.staff_member.canonical', ['staff_member' => $entity->id()]);
  }
}
