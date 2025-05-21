<?php

namespace Drupal\myportal_news\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the NewsFeedServiceInterface trait.
 *
 * @package Drupal\myportal_news\Service
 */
interface NewsFeedServiceInterface {

  /**
   * Retrieve the News.
   *
   * @param string $lang_code
   *   The language code.
   * @param int $count
   *   The count news.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account destination of news.
   *
   * @return \Drupal\myportal_news\Model\News[]
   *   The news list found.
   */
  public function getNews(string $lang_code, int $count = 10, AccountInterface $account = NULL): array;

}
