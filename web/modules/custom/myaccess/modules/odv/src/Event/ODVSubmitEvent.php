<?php

declare(strict_types=1);

namespace Drupal\odv\Event;

use Drupal\odv\DTO\Submission;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event for ODV submission.
 */
class ODVSubmitEvent extends Event {

  /**
   * An ODV submission.
   *
   * @var \Drupal\odv\DTO\Submission
   */
  private Submission $submission;

  /**
   * ODVSubmitEvent constructor.
   *
   * @param \Drupal\odv\DTO\Submission $submission
   *   An ODV submission.
   */
  public function __construct(Submission $submission) {
    $this->submission = $submission;
  }

  /**
   * Get the ODV submission.
   *
   * @return \Drupal\odv\DTO\Submission
   *   The ODV submission.
   */
  public function getSubmission(): Submission {
    return $this->submission;
  }

}
