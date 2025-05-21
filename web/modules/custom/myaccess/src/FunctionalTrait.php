<?php

declare(strict_types=1);

namespace Drupal\myaccess;

/**
 * Collection of functional code.
 */
trait FunctionalTrait {

  /**
   * Pass all elements of the $iterable to the $func function.
   *
   * @param iterable $iterable
   *   An $iterable.
   * @param callable $func
   *   A function.
   *
   * @return array
   *   All elements of $iterable passed throughout $func
   */
  public function map(iterable $iterable, callable $func): array {
    $temp = [];

    foreach ($iterable as $key => $val) {
      $temp[$key] = $func($val, $key);
    }

    return $temp;
  }

}
