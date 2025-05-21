<?php

declare(strict_types=1);

namespace Drupal\myaccess\Commands;

use Drupal\Core\Database\Statement;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myaccess\Hmrs\ClientInterface;
use Drupal\myaccess\Model\HmrsUserRecord;
use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Connection;

/**
 * Define HmrsCommands, used for sync data and clean data.
 *
 * @package Drupal\myaccess\Commands
 */
class HmrsCommands extends DrushCommands {

  /**
   * The database connection used to check the IP against.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The HmrsClient service.
   *
   * @var \Drupal\myaccess\Hmrs\ClientInterface
   */
  protected $hmrsClient;

  /**
   * EntityType manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HmrsCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\myaccess\Hmrs\ClientInterface $hmrs_client
   *   The HmrsClient service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $connection, ClientInterface $hmrs_client, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();
    $this->connection = $connection;
    $this->hmrsClient = $hmrs_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * This command was used for update the values of 'group_scope' of groups.
   *
   * @command hmrs:sync-group-scope
   * @see https://wellnet.atlassian.net/browse/MEN-815
   * @psalm-suppress NoInterfaceProperties
   */
  public function syncGroupScope() {
    // Retrieve all hierarchy.
    $all_hierarchy = $this->hmrsClient->getAllHierarchy();

    $data = [];
    foreach ($all_hierarchy as $item) {
      // Recreate the user records.
      $data[] = new HmrsUserRecord(
        $item['POS_POSGLOBALCODE'],
        TRUE,
        $item['POS_TITLE_LOCAL'],
        $item['POS_COMPANYCODE'],
        $item['POS_DIVISIONCODE'],
        $item['POS_DEPARTMENTCODE'],
        $item['POS_SUBAREACODE'],
        $item['POS_SUBAREA2CODE'],
        $item['POS_SUBAREA3CODE'],
        $item['POS_SUBAREA4CODE'],
        $item['POS_SUBAREA5CODE'],
        $item['POS_SUBAREA6CODE'],
        $item['POS_SUBAREA7CODE'],
        $item['POS_FUNCTIONCODE'],
        $item['POS_SUBFUNCTIONCODE'],
        $item['POS_LEGALENTITYCODE'],
        $item['POS_REGIONCODE'],
        $item['POS_COUNTRYCODE'],
        '',
        $item['POS_LOCATIONCODE'],
        $item['POS_FUNCTIONALAREA'] ?? '',
        $item['POS_AREACODE'],
      );
    }
    $this->output()->writeln("Found " . count($data) . " records.");

    $user_data = $this->hmrsClient->buildUserData($data);
    $records = $user_data->getRecords();

    $group_storage = $this->entityTypeManager->getStorage('group');
    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = $group_storage->loadByProperties(['type' => 'flexible_group']);

    foreach ($groups as $group) {
      $key = array_search($group->label(), array_column($records, 'name'));
      if ($key === FALSE) {
        $this->output()
          ->writeln("Group {$group->label()}({$group->id()}) not found in records from HRMS.");
      }
      else {
        // Update the group scope if required.
        $scope = $records[$key]['scope'];
        if ($group->get('field_group_scope')->value != $scope) {
          $group->set('field_group_scope', $scope);
          $group->save();
          $this->output()->writeln("Saved {$group->label()}.");
        }
      }
    }
  }

  /**
   * Update Hrms.
   *
   * @command hmrs:sync
   *
   * @usage hmrs:sync
   */
  public function sync() {
    $hrms_all_hierarchy = $this->hmrsClient->getAllHierarchy();
    if (!empty($hrms_all_hierarchy) && is_array($hrms_all_hierarchy)) {
      $totalItems = count($hrms_all_hierarchy);
      $message = "Importing [$totalItems] items";
      $this->output()->writeln($message);
    }
    else {
      $this->logger()->error('SYNC ERROR');
    }

    $insert_imported = 0;
    $update_imported = 0;
    if (!empty($hrms_all_hierarchy)) {
      foreach ($hrms_all_hierarchy as $row) {
        $records = $this->searchPosition($row['POS_POSGLOBALCODE']);
        $this->output()->write('.');
        if (!empty($records) && count($records)) {
          if ($this->updateData($row)) {
            $update_imported++;
          }
        }
        else {
          if ($this->insertData($row)) {
            $insert_imported++;
          }
        }
      }

      $this->output()->writeln("\r\n$insert_imported items imported.");
      $this->output()->writeln("\r\n$update_imported update imported.");
    }

  }

  /**
   * Clean Hrms.
   *
   * @command hmrs:clean
   *
   * @usage hmrs:clean
   */
  public function clean() {
    $delete_code = 0;
    $hrms_all_hierarchy = $this->hmrsClient->getAllHierarchy();
    if (!empty($hrms_all_hierarchy) && is_array($hrms_all_hierarchy)) {
      $global_code = array_column($hrms_all_hierarchy, 'POS_POSGLOBALCODE');
    }
    else {
      $this->logger()->error('CLEAN ERROR');
    }

    if (!empty($hrms_all_hierarchy) && !empty($global_code)) {
      $records = $this->searchPosition();
      if (!empty($records) && count($records)) {
        foreach ($records as $item) {
          if (!in_array($item->POS_POSGLOBALCODE, $global_code)) {
            $this->deleteData((int) $item->id);
            $delete_code++;
            $this->output()->write('.');
          }
        }
        $delete_message = $delete_code . ' deleted';
        $this->output()->writeln("\r\n" . $delete_message);
      }
    }
  }

  /**
   * Update the custom table with positions.
   *
   * @param array $row
   *   Array with the data to update.
   *
   * @return bool
   *   Returns true if the update was successful.
   *
   * @throws \Exception
   */
  protected function updateData(array $row): bool {
    try {
      $this->connection->update('hmrs_mapping')
        ->fields([
          'POS_TITLE_LOCAL' => $row['POS_TITLE_LOCAL'],
          'POS_TITLE_ENGLISH' => $row['POS_TITLE_ENGLISH'],
          'POS_COMPANYCODE' => $row['POS_COMPANYCODE'],
          'POS_DIVISIONCODE' => $row['POS_DIVISIONCODE'],
          'POS_DEPARTMENTCODE' => $row['POS_DEPARTMENTCODE'],
          'POS_SUBAREACODE' => $row['POS_SUBAREACODE'],
          'POS_SUBAREA2CODE' => $row['POS_SUBAREA2CODE'],
          'POS_SUBAREA3CODE' => $row['POS_SUBAREA3CODE'],
          'POS_SUBAREA4CODE' => $row['POS_SUBAREA4CODE'],
          'POS_SUBAREA5CODE' => $row['POS_SUBAREA5CODE'],
          'POS_SUBAREA6CODE' => $row['POS_SUBAREA6CODE'],
          'POS_SUBAREA7CODE' => $row['POS_SUBAREA7CODE'],
          'POS_FUNCTIONCODE' => $row['POS_FUNCTIONCODE'],
          'POS_SUBFUNCTIONCODE' => $row['POS_SUBFUNCTIONCODE'],
          'POS_LEGALENTITYCODE' => $row['POS_LEGALENTITYCODE'],
          'POS_REGIONCODE' => $row['POS_REGIONCODE'],
          'POS_COUNTRYCODE' => $row['POS_COUNTRYCODE'],
          'POS_LOCATIONCODE' => $row['POS_LOCATIONCODE'],
          'POS_FUNCTIONALAREA' => $row['POS_FUNCTIONALAREA'] ?? '',
          'POS_AREACODE' => $row['POS_AREACODE'],
        ])
        ->condition('POS_POSGLOBALCODE', $row['POS_POSGLOBALCODE'])
        ->execute();

      return TRUE;
    }
    catch (\PDOException $exception) {
      $this->logger()
        ->error('HmrsCommand throw exception in "@method": @message.', [
          '@method' => 'updateData',
          '@message' => $exception->getMessage(),
        ]);
      $this->connection->rollBack();

      return FALSE;
    }

  }

  /**
   * Populate the custom table with positions.
   *
   * @param array $row
   *   Array with the data to insert.
   *
   * @return bool
   *   Returns true if the insertion was successful.
   *
   * @throws \Exception
   */
  protected function insertData(array $row): bool {
    try {
      $this->connection->insert('hmrs_mapping')
        ->fields([
          'POS_POSGLOBALCODE',
          'POS_TITLE_LOCAL',
          'POS_TITLE_ENGLISH',
          'POS_COMPANYCODE',
          'POS_DIVISIONCODE',
          'POS_DEPARTMENTCODE',
          'POS_SUBAREACODE',
          'POS_SUBAREA2CODE',
          'POS_SUBAREA3CODE',
          'POS_SUBAREA4CODE',
          'POS_SUBAREA5CODE',
          'POS_SUBAREA6CODE',
          'POS_SUBAREA7CODE',
          'POS_FUNCTIONCODE',
          'POS_SUBFUNCTIONCODE',
          'POS_LEGALENTITYCODE',
          'POS_REGIONCODE',
          'POS_COUNTRYCODE',
          'POS_LOCATIONCODE',
          'POS_FUNCTIONALAREA',
          'POS_AREACODE',
        ])
        ->values([
          'POS_POSGLOBALCODE' => $row['POS_POSGLOBALCODE'],
          'POS_TITLE_LOCAL' => $row['POS_TITLE_LOCAL'],
          'POS_TITLE_ENGLISH' => $row['POS_TITLE_ENGLISH'],
          'POS_COMPANYCODE' => $row['POS_COMPANYCODE'],
          'POS_DIVISIONCODE' => $row['POS_DIVISIONCODE'],
          'POS_DEPARTMENTCODE' => $row['POS_DEPARTMENTCODE'],
          'POS_SUBAREACODE' => $row['POS_SUBAREACODE'],
          'POS_SUBAREA2CODE' => $row['POS_SUBAREA2CODE'],
          'POS_SUBAREA3CODE' => $row['POS_SUBAREA3CODE'],
          'POS_SUBAREA4CODE' => $row['POS_SUBAREA4CODE'],
          'POS_SUBAREA5CODE' => $row['POS_SUBAREA5CODE'],
          'POS_SUBAREA6CODE' => $row['POS_SUBAREA6CODE'],
          'POS_SUBAREA7CODE' => $row['POS_SUBAREA7CODE'],
          'POS_FUNCTIONCODE' => $row['POS_FUNCTIONCODE'],
          'POS_SUBFUNCTIONCODE' => $row['POS_SUBFUNCTIONCODE'],
          'POS_LEGALENTITYCODE' => $row['POS_LEGALENTITYCODE'],
          'POS_REGIONCODE' => $row['POS_REGIONCODE'],
          'POS_COUNTRYCODE' => $row['POS_COUNTRYCODE'],
          'POS_LOCATIONCODE' => $row['POS_LOCATIONCODE'],
          'POS_FUNCTIONALAREA' => $row['POS_FUNCTIONALAREA'] ?? '',
          'POS_AREACODE' => $row['POS_AREACODE'],
        ])
        ->execute();

      return TRUE;
    }
    catch (\PDOException $exception) {
      $this->logger()
        ->error('HmrsCommand throw exception in "@method": @message.', [
          '@method' => 'insertData',
          '@message' => $exception->getMessage(),
        ]);
      $this->connection->rollBack();

      return FALSE;
    }
  }

  /**
   * Search position in hrms_mapping.
   *
   * @param string $code
   *   POS_POSGLOBALCODE used for condition.
   *
   * @return array
   *   Return the position items of the search.
   */
  protected function searchPosition(string $code = ''): array {
    $records = [];
    $query = $this->connection->select('hmrs_mapping', 'hmrs_m');
    $query->fields('hmrs_m', ['id', 'POS_POSGLOBALCODE']);
    if (!empty($code)) {
      $query->condition('POS_POSGLOBALCODE', $code);
    }

    $results = $query->execute();
    if (!empty($results) && $results instanceof Statement) {
      $positions = $results->fetchAll(\PDO::FETCH_OBJ);
      if (!empty($positions)) {
        foreach ($positions as $position) {
          $records[] = $position;
        }
      }

      return $records;
    }

    return $records;
  }

  /**
   * Delete single position in hmrs_mapping.
   *
   * @param int $id
   *   Id to be deleted.
   */
  protected function deleteData(int $id) {
    try {
      $query = $this->connection->delete('hmrs_mapping');
      $query->condition('id', $id);
      $query->execute();

    }
    catch (\PDOException $exception) {
      $this->logger()
        ->error('HmrsCommand throw exception in "@method": @message.', [
          '@method' => 'deleteData',
          '@message' => $exception->getMessage(),
        ]);
      $this->connection->rollBack();
    }

  }

}
