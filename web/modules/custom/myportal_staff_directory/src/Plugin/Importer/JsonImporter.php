<?php

namespace Drupal\myportal_staff_directory\Plugin\Importer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\myportal_staff_directory\Plugin\ImporterBase;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\myportal_staff_directory\Entity\BackupInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Staff directory importer from a JSON format.
 *
 * @Importer(
 *   id = "json",
 *   label = @Translation("JSON Importer")
 * )
 */
class JsonImporter extends ImporterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function import() {
    /** @var \Drupal\myportal_staff_directory\Entity\ImporterInterface $config */
    $config = $this->configuration['config'];
    $request = $this->httpClient->request('GET', $config->getUrl()->toString(), [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $this->getAccessToken(),
      ]
    ]);

    $string = $request->getBody()->getContents();
    $data = json_decode($string, TRUE);

    // Json validation, check scalability
    if (json_last_error() !== JSON_ERROR_NONE) {
      $message = 'Invalid JSON, import aborted';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }

    $this->resetData();

    $result = $this->saveEntities($data);

    if ($result !== FALSE) {
      $this->createBackup($string);
    } else {
      $this->restoreLastBackup();

      $message = 'Error during saving entities, last backup restored';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }

    return $result;
  }

  /**
   * Retrieve an access token to be used in subsequent API calls.
   *
   * @return string
   *   The token string.
   */
  private function getAccessToken(): string {
    $config_myaccess = $this->configFactory->get('myaccess.settings');
    $authenticate_url = $config_myaccess->get('hmrs.hmrs_authenticate');
    $username = $config_myaccess->get('hmrs.username');
    $password = $config_myaccess->get('hmrs.password');

    try {
      $response = $this->httpClient->request('POST', $authenticate_url, [
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'username' => $username,
          'password' => $password,
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $token = json_decode($response->getBody()->getContents(), TRUE);

        return $token['id_token'];
      } else {
        return '';
      }
    } catch (GuzzleException $e) {
      return '';
    }
  }

  /**
   * Restore a backup entity
   */
  public function restoreBackup(BackupInterface &$entity) {
    $file_uri = $entity->file->entity->getFileUri();
    $filepath = \Drupal::service('file_system')->realpath($file_uri);

    $string = file_get_contents($filepath);
    $data = json_decode($string, TRUE);

    // Json validation, check scalability
    if (json_last_error() !== JSON_ERROR_NONE) {
      $message = 'Invalid JSON, restore aborted';
      $this->sendErrorNotification($message);
      throw new PluginException($message);
    }

    $this->resetData();
    $this->saveEntities($data);

    return TRUE;
  }
}
