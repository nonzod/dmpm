<?php

namespace Drupal\o11y_traces\Twig\Extension;

use Drupal\o11y_traces\Opentracing;
use Twig\Profiler\Profile;

/**
 * Class ProfilerExtension
 */
class ProfilerExtension extends \Twig_Extension_Profiler {

  private $tracing;

  private $events;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Twig_Profiler_Profile $profile,
    Opentracing $tracing
  ) {
    parent::__construct($profile);

    $this->tracing = $tracing;
    $this->events = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function enter(Profile $profile) {
    if ($profile->isTemplate()) {
      $this->events[$profile] = $this->tracing->startSpan('Twig: ' . $profile->getName());
    }

    parent::enter($profile);
  }

  /**
   * {@inheritdoc}
   */
  public function leave(Profile $profile) {
    parent::leave($profile);

    if ($profile->isTemplate()) {
      $this->events[$profile]->finish();
      unset($this->events[$profile]);
    }
  }

}
