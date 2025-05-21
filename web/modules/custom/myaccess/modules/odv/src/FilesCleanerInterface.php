<?php

declare(strict_types=1);

namespace Drupal\odv;

/**
 * Provides an interface for files cleaner service.
 */
interface FilesCleanerInterface {

  /**
   * Clean files.
   */
  public function clean(): void;

}
