<?php

namespace Drupal\myportal_staff_directory\Commands;

use Drupal\myportal_staff_directory\Plugin\ImporterPluginInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputOption;
use Drupal\myportal_staff_directory\Plugin\ImporterManager;

/**
 * Drush commands for staff members.
 */
class StaffMemberCommands extends DrushCommands {

  /**
   * The importer plugin manager.
   *
   * @var \Drupal\myportal_staff_directory\Plugin\ImporterManager
   */
  protected $importerManager;

  /**
   * StafMemberCommands constructor.
   *
   * @param \Drupal\myportal_staff_directory\Plugin\ImporterManager $importerManager
   *   The importer plugin manager.
   */
  public function __construct(ImporterManager $importerManager) {
    $this->importerManager = $importerManager;
  }

  /**
   * Imports the StaffMembers.
   *
   * @param array $options
   *   The command options.
   *
   * @option importer
   *   The importer config ID to use.
   *
   * @command staff-member-import-run
   * @aliases sm-ir
   */
  public function import(array $options = ['importer' => InputOption::VALUE_OPTIONAL]) {
    $importer = $options['importer'];

    if (!is_null($importer)) {
      $plugin = $this->importerManager->createInstanceFromConfig($importer);
      if (is_null($plugin)) {
        $this->logger()->log('error', t('The specified importer does not exist.'));
        return json_encode(['error' => 'The specified importer does not exist.']);;
      }

      $this->runPluginImport($plugin);
      return json_encode(['result' => 'ok']);
    }

    $plugins = $this->importerManager->createInstanceFromAllConfigs();
    if (!$plugins) {
      $this->logger()->log('error', t('There are no importers to run.'));
      return;
    }

    foreach ($plugins as $plugin) {
      $this->runPluginImport($plugin);
    }
  }

  /**
   * Runs an individual Importer plugin.
   */
  protected function runPluginImport(ImporterPluginInterface $plugin) {
    $result = $plugin->import();
    $message_values = ['@importer' => $plugin->getConfig()->label()];
    if ($result) {
      $this->logger()->log('info', t('The "@importer" importer has been run.', $message_values));
      return;
    }

    $this->logger()->log('error', t('There was a problem running the "@importer" importer.', $message_values));
  }

}
