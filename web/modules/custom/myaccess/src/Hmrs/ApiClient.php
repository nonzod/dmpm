<?php

//@msg_clean

declare(strict_types=1);

namespace Drupal\myaccess\Hmrs;

use Concat\Http\Middleware\Logger;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Statement;
use Drupal\myaccess\Exception\UserDataRetrievalException;
use Drupal\myaccess\Model\HmrsUserData;
use Drupal\myaccess\Model\HmrsUserRecord;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use Psr\Log\LoggerInterface;

/**
 * Hmrs client that reads user data from a HMRS Menarini system.
 *
 * @package Drupal\myaccess\Hmrs
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApiClient implements ClientInterface {

  use HmrsTrait;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The HttpClient.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * The database connection used to check the IP against.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * ApiClient constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Database service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config factory service.
   * @param \Psr\Log\LoggerInterface $logger_http_client
   *   The Logger service dedicated to http_client.
   */
  public function __construct(LoggerInterface $logger, GuzzleInterface $http_client, Connection $connection, ConfigFactoryInterface $config_factory, LoggerInterface $logger_http_client) {
    $this->logger = $logger;
    $this->httpClient = $http_client;
    $this->connection = $connection;
    $this->configFactory = $config_factory;

    $stack = HandlerStack::create();
    $middleware_log = new Logger($logger_http_client, $this->getDefaultFormatter());
    $stack->push($middleware_log);
    $this->httpClient = new Client(['handler' => $stack]);
  }

  /**
   * {@inheritDoc}
   */
  public function getUserData(string $email, bool $onlyPrimary = false): ?HmrsUserData {
    $data = [];

    try {
      $user_position = $this->userPosition($email);

      if (empty($user_position)) {
        return NULL;
      }

      foreach ($user_position as $position) {
        // Parse the POS_PRIMARY_POSGLOBALCODE to extract if is the primary position.
        $is_primary_pos = isset($position['POS_PRIMARY_POSGLOBALCODE']) ? filter_var($position['POS_PRIMARY_POSGLOBALCODE'], FILTER_VALIDATE_BOOLEAN) : FALSE;

        //Check if it needs only the data for the primary position or not
        if( !$onlyPrimary || $is_primary_pos) {

          // Parse the POS_ESTINT to extract the value of external.
          $is_external = FALSE;
          if (!empty($position['POS_ESTINT']) && $position['POS_ESTINT'] === 'E') {
            $is_external = TRUE;
          }

          // Parse the ISMANAGER to extract the value of manager.
          $is_manager = isset($position['ISMANAGER']) ? filter_var($position['ISMANAGER'], FILTER_VALIDATE_BOOLEAN) : FALSE;

          $hierarchy_by_position = $this->getHierarchyByPosition($position['POS_POSCODE'], $is_external, $is_manager, $is_primary_pos);
          if ($hierarchy_by_position == NULL) {
            $hierarchy_by_position = $this->getHierarchyByPositionApi($position['POS_POSCODE'], $is_external, $is_manager, $is_primary_pos);
          }

          if ($hierarchy_by_position != NULL) {
            $data[] = $hierarchy_by_position;
          }

        }
      }
    }
    catch (GuzzleException $e) {
      throw new UserDataRetrievalException($email, 'hmrs_mapping');
    }

    return $this->buildUserData($data);
  }

  /**
   * {@inheritDoc}
   */
  public function getAllHierarchy(): array {
    try {
      $data = [];
      $endpoint = $this->configFactory->get('myaccess.settings')
        ->get('hmrs.base_uri');
      $response = $this->httpClient->request('GET', $endpoint, [
        'query' => [
          'WsName' => 'ALL_HIERARCHY',
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (!is_null($data) && is_array($data)) {
          return $data;
        }
      }

      return $data;
    }
    catch (GuzzleException $e) {
      $this->logger->error('ApiClient throw exception in "@method": @message.', [
        '@method' => 'getAllHierarchy',
        '@message' => $e->getMessage(),
      ]);

      return [];
    }
  }

  /**
   * Returns the default formatter.
   *
   * @return \GuzzleHttp\MessageFormatter
   *   The message formatter.
   */
  protected function getDefaultFormatter() {
    return new MessageFormatter(_myaccess_get_template_message_formatter());
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
      }
      else {
        return '';
      }

    }
    catch (GuzzleException $e) {
      $this->logger->error('ApiClient throw exception in "@method": @message.', [
        '@method' => 'getAccessToken',
        '@message' => $e->getMessage(),
      ]);

      return '';
    }
  }

  /**
   * Retrieve all data for a user position from the cache table.
   *
   * @param string $code
   *   The position code.
   * @param bool $external
   *   If the user is external from the HMRS standpoint.
   * @param bool $manager
   *   If the user is manager from the HMRS standpoint.
   * @param bool $primary_pos
   *    If the position is the primary position from the HMRS standpoint.
   *
   * @return \Drupal\myaccess\Model\HmrsUserRecord|null
   *   All data for a user position from the cache table.
   */
  private function getHierarchyByPosition(string $code, bool $external, bool $manager, bool $primary_pos): ?HmrsUserRecord {
    if (empty($code)) {
      return NULL;
    }

    try {
      $query = $this->connection->select('hmrs_mapping', 'hmrs_m');
      $query->fields('hmrs_m');
      $query->condition('POS_POSGLOBALCODE', $code);
      $results = $query->execute();
      if (!empty($results) && $results instanceof Statement) {
        $records = $results->fetchAll(\PDO::FETCH_OBJ);
        if (!empty($records) && count($records)) {
          $record = reset($records);

          return new HmrsUserRecord(
            $record->POS_POSGLOBALCODE,
            $external,
            $manager,
            $record->POS_TITLE_LOCAL,
            $record->POS_COMPANYCODE,
            $record->POS_DIVISIONCODE,
            $record->POS_DEPARTMENTCODE,
            $record->POS_SUBAREACODE,
            $record->POS_SUBAREA2CODE,
            $record->POS_SUBAREA3CODE,
            $record->POS_SUBAREA4CODE,
            $record->POS_SUBAREA5CODE,
            $record->POS_SUBAREA6CODE,
            $record->POS_SUBAREA7CODE,
            $record->POS_FUNCTIONCODE,
            $record->POS_SUBFUNCTIONCODE,
            $record->POS_LEGALENTITYCODE,
            $record->POS_REGIONCODE,
            $record->POS_COUNTRYCODE,
            '',
            $record->POS_LOCATIONCODE,
            $record->POS_FUNCTIONALAREA ?? '',
            $record->POS_AREACODE,
            $primary_pos
          );
        }
      }
    }
    catch (\PDOException $e) {
      $this->logger->error('ApiClient throw exception in "@method": @message.', [
        '@method' => 'getHierarchyByPosition',
        '@message' => $e->getMessage(),
      ]);
      $this->connection->rollBack();
    }

    return NULL;
  }

  /**
   * Retrieve all data for a user position from an API call.
   *
   * @param string $code
   *   The position code.
   * @param bool $external
   *   If the user is external from the HMRS standpoint.
   * @param bool $manager
   *   If the user is manager from the HMRS standpoint.
   * @param bool $primary_pos
   *   If the position is the primary position from the HMRS standpoint.
   *
   * @return \Drupal\myaccess\Model\HmrsUserRecord|null
   *   All data for a user position from an API call.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getHierarchyByPositionApi(string $code, bool $external, bool $manager, bool $primary_pos): ?HmrsUserRecord {
    try {
      $endpoint = $this->configFactory->get('myaccess.settings')
        ->get('hmrs.base_uri');
      $response = $this->httpClient->request('GET', $endpoint, [
        'query' => [
          'WsName' => 'HIERARCHY_BY_POSITION',
          'param1' => $code,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ],
      ]);

      $data = NULL;
      if ($response->getStatusCode() === 200) {
        $response = json_decode($response->getBody()->getContents(), TRUE);
        $response = reset($response);

        $data = new HmrsUserRecord(
          $response['POS_POSGLOBALCODE'],
          $external,
          $manager,
          $response['POS_TITLE_LOCAL'],
          $response['POS_COMPANYCODE'],
          $response['POS_DIVISIONCODE'],
          $response['POS_DEPARTMENTCODE'],
          $response['POS_SUBAREACODE'],
          $response['POS_SUBAREA2CODE'],
          $response['POS_SUBAREA3CODE'],
          $response['POS_SUBAREA4CODE'],
          $response['POS_SUBAREA5CODE'],
          $response['POS_SUBAREA6CODE'],
          $response['POS_SUBAREA7CODE'],
          $response['POS_FUNCTIONCODE'],
          $response['POS_SUBFUNCTIONCODE'],
          $response['POS_LEGALENTITYCODE'],
          $response['POS_REGIONCODE'],
          $response['POS_COUNTRYCODE'],
          '',
          $response['POS_LOCATIONCODE'],
          $response['POS_FUNCTIONALAREA'] ?? '',
          $response['POS_AREACODE'],
          $primary_pos
        );
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('ApiClient throw exception in "@method": @message.', [
        '@method' => 'getHierarchyByPositionApi',
        '@message' => $e->getMessage(),
      ]);

      return NULL;
    }
  }

  /**
   * Retrieve all the user positions in the HMRS system.
   *
   * @param string $email
   *   The user email address.
   *
   * @return array
   *   Return the array with the API response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function userPosition(string $email): array {
    $data = [];

    try {
      $endpoint = $this->configFactory
        ->get('myaccess.settings')
        ->get('hmrs.base_uri');
      $response = $this->httpClient->request('GET', $endpoint, [
        'query' => [
          'WsName' => 'POSITIONS_BY_MAIL',
          'param1' => $email,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($data)) {
          // If the user does not exist, the API returns empty.
          if (empty($data)) {
            return $data;
          }
        }
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('ApiClient throw exception in "@method": @message.', [
        '@method' => 'userPosition',
        '@message' => $e->getMessage(),
      ]);

      return $data;
    }
  }

}
