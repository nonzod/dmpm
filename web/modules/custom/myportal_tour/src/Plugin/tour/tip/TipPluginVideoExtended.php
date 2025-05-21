<?php

namespace Drupal\myportal_tour\Plugin\tour\tip;

use Drupal\Core\Form\FormStateInterface;

/**
 * Displays some video as a tip.
 *
 * @Tip(
 *   id = "myp_video_extended",
 *   title = @Translation("Video")
 * )
 */
class TipPluginVideoExtended extends TipPluginVideo {

  /**
   * {@inheritdoc}
   *
   * @todo Remove this method when
   * https://www.drupal.org/node/2851166#comment-11925707 will be commited.
   */
  public function getConfiguration() {
    $properties = [];
    $names = [
      'id',
      'plugin',
      'label',
      'weight',
      'attributes',
      'location',
      'video',
    ];

    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   *  @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $id = $this->get('id');
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => $this->get('label'),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\tour\Entity\Tour::load',
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ],
      '#default_value' => $id,
      '#disabled' => !empty($id),
    ];
    $form['plugin'] = [
      '#type' => 'value',
      '#value' => $this->get('plugin'),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $this->get('weight'),
      '#attributes' => [
        'class' => ['tip-order-weight'],
      ],
      '#delta' => 100,
    ];
    $attributes = $this->getAttributes();

    $form['attributes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Attributes'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];

    $data_id = '';
    if (!empty($attributes['data-id'])) {
      $tip_type = 'data-id';
      $data_id = $attributes['data-id'];
    }
    elseif (!empty($attributes['data-class'])) {
      $tip_type = 'data-class';
    }
    else {
      $tip_type = 'modal';
    }

    $form['attributes']['selector_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector type'),
      '#description' => $this->t('The type of selector that this tip will target.'),
      '#options' => [
        'data-id' => $this->t('Data ID'),
        'data-class' => $this->t('Data Class'),
        'modal' => $this->t('Modal'),
      ],
      '#default_value' => $tip_type,
      '#element_validate' => [[$this, 'optionsFormValidate']],
    ];
    $form['attributes']['data-id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data id'),
      '#description' => $this->t('Provide the ID of the page element.'),
      '#field_prefix' => '#',
      '#default_value' => $data_id,
      '#states' => [
        'visible' => [
          'select[name="attributes[selector_type]"]' => ['value' => 'data-id'],
        ],
        'enabled' => [
          'select[name="attributes[selector_type]"]' => ['value' => 'data-id'],
        ],
      ],
    ];
    $form['attributes']['data-class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data class'),
      '#description' => $this->t('Provide the Class of the page element. You can provide more complex jquery selection like <pre>action-links a[href="/admin/structure/forum/add/forum"]</pre>'),
      '#field_prefix' => '.',
      '#default_value' => !empty($attributes['data-class']) ? $attributes['data-class'] : '',
      '#states' => [
        'visible' => [
          'select[name="attributes[selector_type]"]' => ['value' => 'data-class'],
        ],
        'enabled' => [
          'select[name="attributes[selector_type]"]' => ['value' => 'data-class'],
        ],
      ],
    ];
    $form['location'] = [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $this->get('location'),
    ];
    $form['video'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Video'),
      '#required' => TRUE,
      '#default_value' => $this->get('video'),
      '#description' => $this->t('Enter the iframe to embed.'),
    ];

    return $form;
  }

  /**
   * Validates the selector_type tip optionsForm().
   *
   * @param mixed $element
   *   The form element that has the validate attached.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form after submission.
   * @param array $form
   *   The form array.
   */
  public function optionsFormValidate($element, FormStateInterface $form_state, array $form) {
    $selector_type = $form_state->get(['attributes', 'selector_type']);
    $form_state->unsetValue(['attributes', 'selector_type']);

    // If selector_type is modal we need to ensure that there is
    // no data-id or data-class specified.
    if ($selector_type == 'modal') {
      $form_state->unsetValue(['attributes', 'data-id']);
      $form_state->unsetValue(['attributes', 'data-class']);
    }

    // If data-id was selected and no id provided.
    if (
      $selector_type == 'data-id' &&
      $form_state->isValueEmpty(['attributes', 'data-id'])
    ) {
      $form_state->setError($form['attributes']['data-id'], $this->t('Please provide a data id.'));
    }

    // If data-class was selected and no class provided.
    if (
      $selector_type == 'data-class' &&
      $form_state->isValueEmpty(['attributes', 'data-class'])
    ) {
      $form_state->setError($form['attributes']['data-class'], $this->t('Please provide a data class.'));
    }

    // Remove the data-class value if data-id is provided.
    if ($selector_type == 'data-id') {
      $form_state->unsetValue(['attributes', 'data-class']);
    }

    // Remove the data-id value is data-class is provided.
    if ($selector_type == 'data-class') {
      $form_state->unsetValue(['attributes', 'data-id']);
    }
  }

}
