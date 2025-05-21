<?php

namespace Drupal\myportal\Twig\Extension;

use Drupal\Core\Datetime\DrupalDateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Defines the DateFormat class.
 *
 * @package Drupal\myportal\Plugin\TwigExtension
 * @see https://git.drupalcode.org/project/twig_tools/-/blob/2.0.x/src/TwigExtension/TwigConvert.php
 */
class DateFormat extends AbstractExtension {

  /**
   * {@inheritDoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('date_from_format', [$this, 'dateFromFormat']),
    ];
  }

  /**
   * Converts a datetime string between different date formats.
   *
   * @param string $value
   *   A datetime string that matches the $from_format date format.
   * @param string $from_format
   *   A PHP datetime format string.
   * @param string $to_format
   *   A PHP datetime format string.
   * @param string|null $from_timezone
   *   The timezone identifier, as described at
   *   http://php.net/manual/timezones.php, the datetime should be
   *   converted from.
   * @param string|null $to_timezone
   *   The timezone identifier, as described at
   *   http://php.net/manual/timezones.php, the datetime should be converted to.
   *
   * @return string|null
   *   The datetime formatted according to the specific data format.
   */
  public static function dateFromFormat(string $value, string $from_format, string $to_format, ?string $from_timezone = NULL, ?string $to_timezone = NULL): ?string {
    // Since a Unix timestamp can be 0 or '0', we need additional
    // empty/falsy checks.
    if (empty($value) && $value !== '0' && $value !== 0) {
      return '';
    }

    try {
      // Create a datetime object from the specified format.
      $converted_date = DrupalDateTime::createFromFormat($from_format, $value, new \DateTimeZone($from_timezone ?? 'UTC'));

      // DateFormatter service.
      /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter_service */
      $date_formatter_service = \Drupal::service('date.formatter');

      // Return the datetime formatted in the specified format.
      return $date_formatter_service
        ->format($converted_date->getTimestamp(), 'custom', $to_format, $to_timezone);
    }
    catch (\Throwable $exception) {
      \Drupal::logger('myportal')->error($exception->getMessage());

      return NULL;
    }
  }

}
