<?php

namespace Drupal\myportal\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Elasticsearch excerpt highlighting processor.
 *
 * @todo remove after close issue #3077596.
 *
 * @SearchApiProcessor(
 *   id = "myportal_elasticsearch_highlight",
 *   label = @Translation("Elasticsearch Highlight"),
 *   description = @Translation("Elasticsearch-based excerpt highlighting."),
 *   stages = {
 *     "preprocess_query" = 0,
 *     "postprocess_query" = 0,
 *   }
 * )
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/highlighting.html
 */
class ElasticsearchHighlight extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'highlight_fields' => [],
      'highlight_type' => 'unified',
      'number_of_fragments' => 3,
      'fragment_size' => 100,
      'pre_tags' => '<span class=\'highlight\'>',
      'post_tags' => '</span>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Select a fulltext field to highlight.
    $fields = $this->index->getFields();
    $fulltext_fields = [];
    foreach ($this->index->getFulltextFields() as $field_id) {
      $fulltext_fields[$field_id] = $fields[$field_id]->getLabel() . ' (' . $field_id . ')';
    }
    $form['highlight_fields'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => $this->t('Fields to highlight'),
      '#description' => $this->t('Select the fields to highlight and display in the excerpt.'),
      '#options' => $fulltext_fields,
      '#default_value' => $this->configuration['highlight_fields'],
    ];

    // Elasticsearch Highlight type.
    // @todo This does not support the Fast vector highlighter due to additional
    //   required configuration but this could be added later.
    $form['highlight_type'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Highlighting type'),
      '#options' => [
        'unified' => $this->t('Unified'),
        'plain' => $this->t('Plain'),
      ],
      '#description' => $this->t(
        'Select the <a href=":url">Elasticsearch highlighting type</a> to use.',
        [
          ':url' => 'https://www.elastic.co/guide/en/elasticsearch/reference/current/highlighting.html',
        ]
      ),
      '#default_value' => $this->configuration['highlight_type'],
    ];

    $form['number_of_fragments'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#title' => $this->t('Number of fragments'),
      '#description' => $this->t('Maximum number of fragments to include in the excerpt.'),
      '#default_value' => $this->configuration['number_of_fragments'],
      '#min' => 1,
    ];
    $form['fragment_size'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#title' => $this->t('Fragment size'),
      '#description' => $this->t('The requested length of each fragment, in characters.'),
      '#default_value' => $this->configuration['fragment_size'],
      '#min' => 0,
    ];
    $form['pre_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighting pre tags'),
      '#description' => $this->t('Text/HTML that will be prepended to all occurrences of search keywords in highlighted text.'),
      '#default_value' => $this->configuration['pre_tags'],
    ];
    $form['post_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighting post tags'),
      '#description' => $this->t('Text/HTML that will be appended to all occurrences of search keywords in highlighted text.'),
      '#default_value' => $this->configuration['post_tags'],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $highlight_fields = $form_state->getValue('highlight_fields');
    $highlight_fields = array_filter($highlight_fields);
    $form_state->setValue('highlight_fields', $highlight_fields);
  }

  /**
   * Retrieves the translated separators for excerpts.
   *
   * Defaults to Unicode ellipses (&mldr;) on all positions.
   *
   * @return string[]
   *   A numeric array containing three elements: the separator to put at the
   *   front of the excerpt (if that is not the front of the string), the
   *   separator to put in between different portions of the text, and the
   *   separator to append at the end of the excerpt if it doesn't end with the
   *   end of the text.
   */
  protected function getEllipses() {
    // Combine the text chunks with "&mldr;" separators.
    // The "&mldr;" needs to be translated.
    // Let translators have the &mldr; separator text as one chunk.
    return explode('@excerpt', $this->t('&mldr; @excerpt &mldr; @excerpt &mldr;'));
  }

  /**
   * Adds excerpts/highlighting to the search results.
   *
   * @param \Drupal\search_api\Item\ItemInterface[] $results
   *   The query result items, keyed by item ID.
   * @param array $response
   *   The Elasticsearch response data.
   */
  protected function addExcerpts(array $results, array $response) {
    $ellipses = $this->getEllipses();
    if (!empty($response['hits']['hits'])) {
      foreach ($response['hits']['hits'] as $result) {

        if (isset($results[$result['_id']])) {

          $highlights = [];
          foreach ($this->configuration['highlight_fields'] as $field) {
            // Skip items with no highlights.
            if (empty($result['highlight'][$field])) {
              continue;
            }
            $highlights = array_merge($highlights, $result['highlight'][$field]);
          }

          if (!empty($highlights)) {
            $excerpt = $ellipses[0] . implode($ellipses[1], $highlights) . $ellipses[2];
            $results[$result['_id']]->setExcerpt($excerpt);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {

    if (empty($this->configuration['highlight_fields'])) {
      return;
    }

    $settings = [
      'type' => $this->configuration['highlight_type'],
      'number_of_fragments' => $this->configuration['number_of_fragments'],
      'fragment_size' => $this->configuration['fragment_size'],
    ];

    foreach ($this->configuration['highlight_fields'] as $field) {
      $settings['fields'][$field] = [
        'pre_tags' => [$this->configuration['pre_tags']],
        'post_tags' => [$this->configuration['post_tags']],
      ];
    }

    $query->setOption('elasticsearch_highlight', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    $query = $results->getQuery();
    if (!$results->getResultCount()
      || $query->getProcessingLevel() != QueryInterface::PROCESSING_FULL) {
      return;
    }

    // Don't try to highlight if there are no search keys.
    $keys = $query->getOriginalKeys();
    if (!$keys) {
      return;
    }

    $result_items = $results->getResultItems();
    $response = $results->getExtraData('elasticsearch_response', []);
    $this->addExcerpts($result_items, $response);
  }

}
