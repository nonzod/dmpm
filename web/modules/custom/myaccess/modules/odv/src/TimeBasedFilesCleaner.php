<?php

declare(strict_types=1);

namespace Drupal\odv;

use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Default Files manager that deletes file older than 10 minutes.
 */
class TimeBasedFilesCleaner implements FilesCleanerInterface {

  use PathTrait;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * The File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * TimeBasedFilesCleaner Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The File system service.
   */
  public function __construct(LoggerInterface $logger, FileSystemInterface $file_system) {
    $this->logger = $logger;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritDoc}
   */
  public function clean(): void {
    $this->logger->info('Start deleting old ODV files');

    $finder = new Finder();
    $finder->files()->in($this->getOdvFolder())->date('< 10 minutes ago');
    foreach ($finder as $file) {
      $this->logger->info($file->getPathname());
      $this->fileSystem->delete($file->getPathname());
    }

    $this->logger->info('Complete deleting old ODV files');
  }

}
