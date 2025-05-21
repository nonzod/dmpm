<?php

namespace Drupal\mylinks_migration\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myaccess\Entity\Application;
use Drupal\myaccess\Exception\GroupNotCreatedException;
use Drupal\myaccess\GroupManagerInterface;
use Drush\Commands\DrushCommands;
use League\Csv\Reader;

/**
 * Drush command to import MyLinks data.
 */
class ImportCommand extends DrushCommands {

  /**
   * The Group manager service.
   *
   * @var \Drupal\myaccess\GroupManagerInterface
   */
  private $groupManager;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * ImportCommand constructor.
   *
   * @param \Drupal\myaccess\GroupManagerInterface $group_manager
   *   The Group manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager service.
   */
  public function __construct(GroupManagerInterface $group_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();

    $this->groupManager = $group_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Import MyLinks from a csv file.
   *
   * @param string $path
   *   The csv file absolute path.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \League\Csv\Exception
   *
   * @command mylinks-migration:import
   */
  public function import(string $path) {
    $csv = $this->readCsv($path);
    $my_links = $this->parseData($csv);

    foreach ($my_links as $my_link) {
      $this->createOrUpdateApplication($my_link);
    }

    $this->io()->success('Finish');
  }

  /**
   * Read the source csv file.
   *
   * @param string $path
   *   The csv file absolute path.
   *
   * @return \League\Csv\Reader
   *   A csv Reader.
   *
   * @throws \League\Csv\Exception
   */
  private function readCsv(string $path): Reader {
    $reader = Reader::createFromPath($path);
    $reader->setHeaderOffset(0);
    $reader->setDelimiter(';');

    return $reader;
  }

  /**
   * Parse csv data.
   *
   * @param \League\Csv\Reader $csv
   *   A csv Reader.
   *
   * @return array
   *   Array of MyLink data.
   */
  private function parseData(Reader $csv): array {
    $my_links = [];
    foreach ($csv->getRecords() as $record) {
      $this
        ->logger()
        ->debug('Importing mylink "@name" with country: "@country", legal entity: "@legal", Position title: "@position"',
          [
            '@name' => $record['Link Name'],
            '@country' => $record['Country'],
            '@legal' => $record['Legal entity'],
            '@position' => $record['Position title'],
          ]
        );

      if (!array_key_exists($record['Link url'], $my_links)) {
        $my_link = [
          'title' => $record['Link Name'],
          'url' => $record['Link url'],
          'weight' => $record['Link Type'] == 'Corporate' ? 1 : 0,
          'groups' => [],
        ];
      }
      else {
        $my_link = $my_links[$record['Link url']];
      }

      try {
        $this->groupManager->createIfNotExists(
          $record['Country'],
          GroupManagerInterface::SCOPE_COUNTRY,
          [GroupManagerInterface::CONTEXT_MYLINKS]);
        $this->groupManager->createIfNotExists(
          $record['Legal entity'],
          GroupManagerInterface::SCOPE_LEGAL_ENTITY,
          [GroupManagerInterface::CONTEXT_MYLINKS]);

        $my_link['groups'][$record['Country']] = $record['Country'];
        $my_link['groups'][$record['Legal entity']] = $record['Legal entity'];

        if ($record['Position title'] != '') {
          $this->groupManager->createIfNotExists(
            $record['Position title'],
            GroupManagerInterface::SCOPE_POSITION_TITLE,
            [GroupManagerInterface::CONTEXT_MYLINKS]);
          $my_link['groups'][$record['Position title']] = $record['Position title'];
        }
      }
      catch (GroupNotCreatedException $exception) {
        $this->logger()->error($exception->getMessage());
      }

      $my_links[$record['Link url']] = $my_link;
    }

    return $my_links;
  }

  /**
   * Create ora update an Application based on MyLink data.
   *
   * @param array $my_link
   *   Array of MyLink data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createOrUpdateApplication(array $my_link) {
    $query = $this->entityTypeManager->getStorage('application')->getQuery();
    $result = $query->condition('url', $my_link['url'])->execute();
    assert(is_array($result));

    if ($result) {
      $application = Application::load(reset($result));

      if ($application) {
        $application->set('title', $my_link['title']);
        $application->set('description', $my_link['title']);
        $application->set('url', $my_link['url']);
        $application->set('weight', $my_link['weight']);
        $application->set('field_application_access',
          $this->getApplicationAccess($my_link['groups']));
        $application->save();
      }
    }
    else {
      $application = Application::create([
        'title' => $my_link['title'],
        'description' => $my_link['title'],
        'url' => $my_link['url'],
        'weight' => $my_link['weight'],
        'field_application_access' => $this->getApplicationAccess($my_link['groups']),
        'status' => 1,
        'bundle' => Application::MYLINKS,
      ]);
      $application->save();
    }
  }

  /**
   * Convert a list of group names in their relative ids.
   *
   * @param array $groups
   *   A list of group names.
   *
   * @return array
   *   A list of group ids.
   */
  private function getApplicationAccess(array $groups): array {
    $application_access = [];

    try {
      foreach ($groups as $group) {
        $query = $this->entityTypeManager->getStorage('group')->getQuery();
        $result = $query->condition('label', $group)->execute();
        assert(is_array($result));

        if ($result) {
          $application_access[] = reset($result);
        }
      }
    }
    catch (\Exception $e) {
      return [];
    }

    return $application_access;
  }

}
