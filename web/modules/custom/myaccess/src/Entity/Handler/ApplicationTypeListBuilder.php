<?php

declare(strict_types=1);

namespace Drupal\myaccess\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\myaccess\Entity\ApplicationType;

/**
 * Defines a class to build a listing of action entities.
 *
 * @package Drupal\myaccess\Entity\Handler
 */
class ApplicationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritDoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Type');
    $header['description'] = $this->t('Description');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritDoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof ApplicationType);
    $row = [];
    $row['label'] = $entity->label();
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

}
