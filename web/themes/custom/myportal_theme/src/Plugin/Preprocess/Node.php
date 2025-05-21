<?php

namespace Drupal\myportal_theme\Plugin\Preprocess;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\socialbase\Plugin\Preprocess\Node as NodeBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node as NodeEntity;

/**
 * Pre-processes variables for the "node" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("node")
 */
class Node extends NodeBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The date formatted.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatted;

  /**
   * Node constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatted
   *   The date formatter service.
   */
  final public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              ConfigFactoryInterface $config,
                              DateFormatterInterface $date_formatted) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config->get('social_event.settings');
    $this->dateFormatted = $date_formatted;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $config = $container->get('config.factory');
    assert($config instanceof ConfigFactoryInterface);

    $date_formatted = $container->get('date.formatter');
    assert($date_formatted instanceof DateFormatterInterface);

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config,
      $date_formatted
    );
  }

  /**
   * {@inheritdoc}
   *
   *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function preprocessElement(Element $element, Variables $variables) {
    parent::preprocessElement($element, $variables);
    /** @var \Drupal\node\Entity\Node $node */
    $node = $variables['node'];
    $view_mode = $variables['view_mode'];
    if ($view_mode === 'hero' || $node->in_preview) {
      // Add the hero styled image.
      $image_field = "field_{$node->getType()}_image";
      if ($node->hasField($image_field) && !empty($node->{$image_field}->entity)) {
        $style = ImageStyle::load('men_hero_large'); // phpcs:ignore
        if ($style != NULL) {
          $url_image = $style->buildUrl($node->{$image_field}->entity->getFileUri());
          $variables['hero_styled_image_url'] = $url_image;
        }
      }
    }

    if ($node->getType() === 'event') {
      $event_date = self::eventFormatDate($node);
      if (is_array($event_date) && !empty($event_date)) {
        $variables['myp_event_date']['day'] = $event_date['event_day'];
        $variables['myp_event_date']['month'] = $event_date['event_month'];
        $variables['myp_event_date']['time'] = !empty($event_date['event_time']) ? $event_date['event_time'] : '';
      }
      $variables['#cache']['contexts'][] = 'timezone';
    }

  }

  /**
   * {@inheritdoc}
   *
   *   @SuppressWarnings(PHPMD.CyclomaticComplexity)
   *   @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function eventFormatDate(NodeEntity $event) {
    $event_date = $end_date = $end_time = '';
    $event_all_day = $double_day = FALSE;
    $add_timezone = $this->config->get('show_user_timezone');

    // This will get the users timezone, which is either set by the user
    // or defaults back to the sites timezone if the user didn't select any.
    $timezone = date_default_timezone_get();
    $user_timezone = date('T');
    if (in_array($user_timezone[0], ['+', '-'])) {
      $user_timezone = 'UTC' . $user_timezone;
    }
    // Timezone that dates should be stored in.
    $utc_timezone = DateTimeItemInterface::STORAGE_TIMEZONE;

    // Get start and end dates.
    if ($start_date_field = $event->get('field_event_date')) {
      if (!empty($start_date_field->getString())) {
        // Since dates are stored as UTC, we will declare our event values
        // as UTC. So we can actually calculate them back to the users timezone.
        // Is necessary because we do not store the event value as being UTC
        // so declaring it with setTimezone will result in wrong values.
        $start_datetime = new \DateTime($start_date_field->getString(), new \DateTimeZone($utc_timezone));
        $start_datetime->setTimezone(new \DateTimeZone($timezone));
        $start_datetime = $start_datetime->getTimestamp();
      }
    }
    if ($end_date_field = $event->get('field_event_date_end')) {
      if (!empty($end_date_field->getString())) {
        // Since dates are stored as UTC, we will declare our event values
        // as UTC. So we can actually calculate them back to the users timezone.
        // Is necessary because we do not store the event value as being UTC
        // so declaring it with setTimezone will result in wrong values.
        $end_datetime = new \DateTime($end_date_field->getString(), new \DateTimeZone($utc_timezone));
        // We now calculate it back to the users timezone.
        $end_datetime->setTimezone(new \DateTimeZone($timezone));
        $end_datetime = $end_datetime->getTimestamp();
      }
    }

    if (!empty($start_datetime)) {
      if ($this->dateFormatted->format($start_datetime, 'custom', 'i') === '01') {
        $start_date = $this->dateFormatted->format($start_datetime, 'event_day', '', $utc_timezone);
        $add_timezone = FALSE;
      }
      else {
        $start_date = $this->dateFormatted->format($start_datetime, 'event_day');
      }
      // Default time should not be displayed.
      $start_time = $this->dateFormatted->format($start_datetime, 'custom', 'i') === '01' ? '' : $this->dateFormatted->format($start_datetime, 'social_time');

      if (!empty($end_datetime)) {
        if ($this->dateFormatted->format($end_datetime, 'custom', 'i') === '01') {
          $end_date = $this->dateFormatted->format($end_datetime, 'event_day', '', $utc_timezone);
        }
        else {
          $end_date = $this->dateFormatted->format($end_datetime, 'event_day');
        }
        // Default time should not be displayed.
        $end_time = $this->dateFormatted->format($end_datetime, 'custom', 'i') === '01' ? '' : $this->dateFormatted->format($end_datetime, 'social_time');
      }

      // Date are the same or there are no end date.
      if (empty($end_datetime) || $start_datetime == $end_datetime) {
        $event_date = empty($start_time) ? $start_date : "$start_date $start_time";
        $event_all_day = TRUE;
      }
      // The date is the same, the time is different.
      elseif (date(DateTimeItemInterface::DATE_STORAGE_FORMAT, $start_datetime) == date(DateTimeItemInterface::DATE_STORAGE_FORMAT, $end_datetime)) {
        $event_date = "$start_date $start_time - $end_time";
        $event_all_day = TRUE;
      }
      // They are not the same day (or empty?).
      elseif (!empty($end_datetime)) {
        $event_date = "$start_date $start_time - $end_date $end_time";
        $double_day = TRUE;
      }
    }

    $myp_event_date = self::dateSplit($event_date, $double_day, $event_all_day, $utc_timezone);

    if (empty($myp_event_date)) {

      return [];
    }

    return [
      'event_day' => $myp_event_date['day'],
      'event_month' => $myp_event_date['month'],
      'event_time' => $myp_event_date['time'],
      'event_years' => $myp_event_date['years'],
      'event_timezone' => $add_timezone ? $user_timezone : '',
    ];
  }

  /**
   * {@inheritdoc}
   *
   *   @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function dateSplit(string $date, $double_day, $event_all_day, $utc_timezone): array {
    if (!$double_day) {
      if ($event_all_day) {
        // The event was marked with every day.
        // Format example : 17 Dec 2020.
        $date_format = \DateTime::createFromFormat("d M Y", $date, new \DateTimeZone($utc_timezone));
        if ($date_format) {

          return [
            'day' => $date_format->format('d'),
            'month' => $date_format->format('M'),
            'years' => $date_format->format('Y'),
            'time' => '',
          ];
        }

        // The event only has a start date and time.
        // Format example : 17 Dec 2020 14:33.
        $date_format = \DateTime::createFromFormat("d M Y H:i", $date, new \DateTimeZone($utc_timezone));
        if ($date_format) {

          return [
            'day' => $date_format->format('d'),
            'month' => $date_format->format('M'),
            'years' => $date_format->format('Y'),
            'time' => $date_format->format('H:i'),
          ];
        }

        // The event has the same date but with different times.
        // Format example : 17 Dec 2020 20:12 30:12.
        $date_format = \DateTime::createFromFormat("d M Y H:i - H:i", $date, new \DateTimeZone($utc_timezone));
        if ($date_format) {
          [$dateTime, $timeEnd] = explode("-", $date);
          $date_format = \DateTime::createFromFormat("d M Y H:i", trim($dateTime), new \DateTimeZone($utc_timezone));

          if ($date_format) {

            return [
              'day' => $date_format->format('d'),
              'month' => $date_format->format('M'),
              'years' => $date_format->format('Y'),
              'time' => $date_format->format('H:i') . ' - ' . trim($timeEnd),
            ];
          }

          return [];
        }

        return [];
      }
    }
    else {
      return self::splitDateDouble($date);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   *
   *   @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public static function splitDateDouble($date): array {
    [$start, $end] = explode("-", $date);

    // Format example : 24 Nov 2020 13:00 - 30 Dec 2020 15:00.
    $start_event = \DateTime::createFromFormat("d M Y H:i", trim($start));
    $end_event = \DateTime::createFromFormat("d M Y H:i", trim($end));

    if ($start_event && $end_event) {
      $day_s = $start_event->format('d');
      $month_s = $start_event->format('M');
      $years_s = $start_event->format('Y');
      $time_s = $start_event->format('H:i');

      $day_e = $end_event->format('d');
      $month_e = $end_event->format('M');
      $years_e = $end_event->format('Y');
      $time_e = $end_event->format('H:i');

      $day = $day_s . '-' . $day_e;
      $month = ($month_s === $month_e) ? $month_s : $month_s . '-' . $month_e;
      $years = ($years_s === $years_e) ? $years_s : $years_s . '-' . $years_e;
      $time = ($time_s === $time_e) ? $time_s : $time_s . '-' . $time_e;

      return [
        'day' => $day,
        'month' => $month,
        'years' => $years,
        'time' => $time,
      ];

    }

    // Format example : 24 Nov 2020 - 30 Dec 2020.
    $start_event = \DateTime::createFromFormat("d M Y", trim($start));
    $end_event = \DateTime::createFromFormat("d M Y", trim($end));

    if ($start_event && $end_event) {
      $day_s = $start_event->format('d');
      $month_s = $start_event->format('M');
      $years_s = $start_event->format('Y');

      $day_e = $end_event->format('d');
      $month_e = $end_event->format('M');
      $years_e = $end_event->format('Y');

      $day = $day_s . '-' . $day_e;
      $month = ($month_s === $month_e) ? $month_s : $month_s . '-' . $month_e;
      $years = ($years_s === $years_e) ? $years_s : $years_s . '-' . $years_e;
      $time = "";

      return [
        'day' => $day,
        'month' => $month,
        'years' => $years,
        'time' => $time,
      ];
    }

    return [];

  }

}
