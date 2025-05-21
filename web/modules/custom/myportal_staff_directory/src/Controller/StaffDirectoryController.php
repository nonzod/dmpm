<?php

namespace Drupal\myportal_staff_directory\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\myportal_staff_directory\Entity\StaffMemberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Controller for Local Admin area
 */
class StaffDirectoryController extends ControllerBase {

  /**
   * The importer plugin manager.
   *
   * @var \Drupal\myportal_staff_directory\Plugin\ImporterManager
   */
  protected $importerManager;

  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->importerManager = $container->get('myportal_staff_directory.staff_member_importer_manager');
    $instance->logger = $container->get('logger.channel.default');

    return $instance;
  }


  /**
   * Overview page
   */
  public function renderIndex() {
    $backups = Link::createFromRoute('Backups', 'entity.import_backup.collection')->toString();
    $importers = Link::createFromRoute('Importers', 'entity.staff_member_importer.collection')->toString();
    $members = Link::createFromRoute('Staff members', 'entity.staff_member.collection')->toString();

    return [
      '#title' => "Staff Directory Admin",
      '#markup' => <<<EOF
        <h4>List of backups for cleanup and restore operations:</h4>
        <ul>
          <li>$backups</li>
        </ul>
        <h4>List of available importers:</h4>
        <ul>
          <li>$importers</li>
        </ul>
        <h4>List of available staff members:</h4>
        <ul>
          <li>$members</li>
        </ul>
      EOF
    ];
  }

  /**
   * @return JsonResponse
   */
  public function staffMemberDetails(StaffMemberInterface $staff_member) {
    return new AjaxResponse($this->getData($staff_member));
  }

  /**
   * 
   */
  public function getData($staff_member) {
    $serializer = \Drupal::service('serializer');

    $staff_member->setTeamToHtml();
    $staff_member->setReportingToHtml();

    $result = $serializer->serialize($staff_member, 'json', ['plugin_id' => 'entity']);

    return $result;
  }

  /**
   * 
   */
  public function runImport(string $importer) {
    $plugin = $this->importerManager->createInstanceFromConfig($importer);
    if (is_null($plugin)) {
      
      new JsonResponse();
    }

    return new JsonResponse($plugin->import());
  }
}
