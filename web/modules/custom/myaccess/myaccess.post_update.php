<?php

/**
 * @file
 * Post update functions for MyAccess.
 */

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\myaccess\Entity\Application;

/**
 * Change Application bundle from 'default' to 'remote'.
 */
function myaccess_post_update_change_application_bundle(&$sandbox) {
  $definition = \Drupal::entityTypeManager()->getDefinition('application');
  $application_storage = \Drupal::entityTypeManager()
    ->getStorage('application');
  assert($definition instanceof EntityTypeInterface);
  $bundle_key = $definition->getKey('bundle');
  assert(is_string($bundle_key) and !empty($bundle_key));
  $applications = $application_storage->loadByProperties([$bundle_key => 'default']);

  /** @var \Drupal\myaccess\Entity\ApplicationInterface $application */
  foreach ($applications as $application) {
    // Search if url of application contains "google".
    $bundle = strpos($application->getUrl(), '.google.') !== FALSE ? Application::GOOGLE : Application::REMOTE;
    $application->{$definition->getKey('bundle')} = $bundle;
    $application->save();
  }
}
