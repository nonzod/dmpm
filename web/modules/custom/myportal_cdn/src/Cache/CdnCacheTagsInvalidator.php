<?php

declare(strict_types=1);

namespace Drupal\myportal_cdn\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myportal_cdn\ComputeService;

/**
 * A cache invalidator service for GCP cdn.
 *
 * The invalidator invalidate the cdn cache if a media entity is changed.
 */
class CdnCacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Google Compute service.
   *
   * @var \Drupal\myportal_cdn\ComputeService
   */
  private $computeService;

  /**
   * CdnCacheTagsInvalidator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManger
   *   The Entity type manager service.
   * @param \Drupal\myportal_cdn\ComputeService $computeService
   *   The Google Compute service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManger, ComputeService $computeService) {
    $this->entityTypeManager = $entityTypeManger;
    $this->computeService = $computeService;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $regexp = '/media:(\d+)/';

    foreach ($tags as $tag) {
      if (preg_match($regexp, $tag, $matches)) {
        $this->invalidateMedia(intval($matches[1]));
      }
    }
  }

  /**
   * Invalide a media from the cdn.
   *
   * @param int $media_id
   *   The media id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Google\Exception
   */
  private function invalidateMedia(int $media_id) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = $this->entityTypeManager->getStorage('media')->load($media_id);
    if ($media == NULL) {
      return;
    }

    /** @var int $fid */
    $fid = $media->getSource()->getSourceFieldValue($media);

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    if ($file == NULL) {
      return;
    }

    $path = $file->createFileUrl();

    $this->computeService->invalidate($path);
  }

}
