<?php

namespace Drupal\myportal_news\Model;

/**
 * Defines the News class.
 *
 * @package Drupal\myportal_news\Model
 */
class News {

  /**
   * The title of news.
   *
   * @var string
   */
  public $title;

  /**
   * The data of news.
   *
   * @var string
   */
  public $data;

  /**
   * The excerpt of news.
   *
   * @var string
   */
  public $excerpt;

  /**
   * The content of news.
   *
   * @var string
   */
  public $content;

  /**
   * The url of news.
   *
   * @var string
   */
  public $url;

  /**
   * Construct new News instance.
   *
   * @param string $title
   *   The title.
   * @param string $data
   *   Posted in.
   * @param string $excerpt
   *   The excerpt.
   * @param string $content
   *   The content.
   * @param string $url
   *   The url.
   */
  public function __construct(string $title, string $data, string $excerpt, string $content, string $url) {
    $this->title = $title;
    $this->data = $data;
    $this->excerpt = $excerpt;
    $this->content = $content;
    $this->url = $url;
  }

  /**
   * Retrieve the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * Retrieve the data.
   *
   * @return string
   *   The data.
   */
  public function getData(): string {
    return $this->data;
  }

  /**
   * Retrieve the excerpt.
   *
   * @return string
   *   The excerpt.
   */
  public function getExcerpt(): string {
    return $this->excerpt;
  }

  /**
   * Retrieve the content.
   *
   * @return string
   *   The content.
   */
  public function getContent(): string {
    return $this->content;
  }

  /**
   * Retrieve the url.
   *
   * @return string
   *   The url.
   */
  public function getUrl(): string {
    return $this->url;
  }

}
