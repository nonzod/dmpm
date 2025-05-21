<?php

declare(strict_types = 1);

namespace Drupal\myportal\Twig\Extension;

/**
 * Class TwigHighlightExtension, used for highlight string.
 */
class TwigHighlightExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'myportal_search.twig_highlight_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('highlight', [$this, 'highlight']),
    ];
  }

  /**
   * Search for a word in the string and highlight it.
   *
   * @param string $text
   *   String content.
   * @param array|null $terms
   *   Terms to search for in the string.
   *
   * @return string|string[]
   *   Returns the string with the searched word highlighted.
   */
  public function highlight(string $text, ?array $terms) {
    if (empty($terms[0])) {
      return $text;
    }

    foreach ($terms as $term) {
      if (preg_match('/"/', $term)) {
        $term = str_replace('"', "", $term);
      }
      $text = preg_replace("/($term)/i", "<span class=highlight>$0</span>", $text);
    }

    return $text;
  }

}
