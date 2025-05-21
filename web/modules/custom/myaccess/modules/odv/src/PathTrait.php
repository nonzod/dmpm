<?php

declare(strict_types=1);

namespace Drupal\odv;

use Drupal\Core\File\FileSystemInterface;

/**
 * Commons functions for path access.
 */
trait PathTrait {

  /**
   * Return the folder used to store odv files.
   *
   * @return string
   *   The folder used to store odv files.
   */
  public function getOdvFolder(): string {
    $folder = 'private://odv';
    if (!file_exists($folder)) {
      $this->getFileSystem()
        ->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    }

    return $folder;
  }

  /**
   * Return the path of the receipt file.
   *
   * @param string $id
   *   The id of the file.
   *
   * @return string
   *   The path of the receipt file.
   */
  public function getReceiptPath(string $id): string {
    return sprintf('%s/%s.zip', $this->getOdvFolder(), $id);
  }

  /**
   * Helper method for returning the file system service.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   The file system service.
   */
  private function getFileSystem() {
    return \Drupal::service('file_system');
  }

}
