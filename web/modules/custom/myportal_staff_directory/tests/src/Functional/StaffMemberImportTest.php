<?php

namespace Drupal\Tests\myportal_staff_directory\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests the staff member import functionality.
 *
 * @group myportal_staff_directory
 */
class StaffMemberImportTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules
    'system',
    'user',
    'field',
    'file',
    'views',
    
    // Contrib modules
    'search_api',
    'facets'
    
    // Custom modules
    'myportal',
    'myportal_group',
    'myportal_staff_directory'
  ];

   /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The staff member importer manager.
   *
   * @var \Drupal\myportal_staff_directory\Plugin\ImporterManager
   */
  protected $importerManager;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Installa le configurazioni necessarie
    $this->installEntitySchema('staff_member');
    $this->installEntitySchema('import_backup');
    $this->installConfig(['myportal_staff_directory']);

    // Crea e login con utente admin
    $this->adminUser = $this->drupalCreateUser([
      'admin staff directory',
      'administer site configuration',
      'access staff directory',
    ]);
    $this->drupalLogin($this->adminUser);

    // Ottieni l'importer manager
    $this->importerManager = $this->container->get('myportal_staff_directory.staff_member_importer_manager');

    // Crea la configurazione di test dell'importer
    $this->createTestImporter();
  }

  /**
   * Creates a test importer configuration.
   */
  protected function createTestImporter() {
    $values = [
      'id' => 'test_importer',
      'label' => 'Test Importer',
      'plugin' => 'json',
      'url' => 'http://example.com/api',
      'retention_days' => 7,
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('staff_member_importer');
    $importer = $storage->create($values);
    $importer->save();
  }

  /**
   * Tests the staff member import process.
   */
  public function testStaffMemberImport() {
    // Test the importer listing page
    $this->drupalGet('admin/structure/staff-directory/staff-member-importer');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test Importer');

    // Test staff member listing before import
    $this->drupalGet('admin/structure/staff-directory/staff-member');
    $this->assertSession()->statusCodeEquals(200);

    // Simula un import con dati di test
    $test_data = $this->getTestStaffData();
    $this->simulateImport($test_data);

    // Verifica i risultati dell'import
    $this->drupalGet('admin/structure/staff-directory/staff-member');
    $this->assertSession()->pageTextContains('John Doe');
  }

  /**
   * Returns test staff data.
   */
  protected function getTestStaffData() {
    return [
      [
        'Globalempcode' => 'EMP001',
        'Name' => 'John',
        'Surname' => 'Doe',
        'Title' => 'Manager',
        'Direct_Line' => '123-456-7890',
        'Mobile' => '098-765-4321',
        'Email' => 'john.doe@example.com',
        'Function' => 'Management',
        'Country' => 'Italy',
        'Legalentity' => 'Test Corp',
        'Superior' => 'jane.smith@example.com',
      ],
    ];
  }

  /**
   * Simulates an import with test data.
   *
   * @param array $test_data
   *   The test data to import.
   */
  protected function simulateImport(array $test_data) {
    $plugin = $this->importerManager->createInstanceFromConfig('test_importer');
    
    // Crea un file temporaneo con i dati di test
    $json_data = json_encode($test_data);
    
    // Salva i dati di test in un file temporaneo
    $file_system = \Drupal::service('file_system');
    $directory = 'private://staff_directory_backup';
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $file_path = $directory . '/test_import.json';
    file_put_contents($file_system->realpath($file_path), $json_data);

    // Esegui l'import
    $plugin->import();
  }

  /**
   * Tests the search functionality.
   */
  public function testSearch() {
    // Prima importa i dati di test
    $test_data = $this->getTestStaffData();
    $this->simulateImport($test_data);

    // Test la pagina di ricerca
    $this->drupalGet('staff-directory');
    $this->assertSession()->statusCodeEquals(200);

    // Test la funzionalità di ricerca
    $this->submitForm([
      'name' => 'John',
    ], 'Apply');
    
    $this->assertSession()->pageTextContains('John Doe');
    $this->assertSession()->pageTextContains('Manager');
  }
}