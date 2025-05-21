<?php

declare(strict_types=1);

namespace Drupal\odv;

use Drupal\odv\DTO\Submission;

/**
 * Interface for services that generates the receipt for the submitter.
 */
interface ReceiptGeneratorInterface {

  /**
   * Generate a receipt file with data from the submission.
   *
   * @param \Drupal\odv\DTO\Submission $submission
   *   An ODV request submission.
   *
   * @return string
   *   The id of the generated file.
   *
   * @throws \Exception
   */
  public function generate(Submission $submission): string;

}
