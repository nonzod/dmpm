<?php

declare(strict_types=1);

namespace Drupal\myportal\Monolog\Processor;

use Monolog\Processor\ProcessorInterface;

/**
 * Defines the ReferenceDrupalProcessor class.
 *
 * @package Drupal\myportal\Logger\Processor
 */
class ReferenceDrupalProcessor implements ProcessorInterface {

  /**
   * {@inheritDoc}
   *
   * @phpstan-ignore-next-line
   */
  public function __invoke(array $record): array {
    $record['extra']['drupal_reference'] = "MyPortal Drupal " . getenv('DRUPAL_ENV');

    return $record;
  }

}
