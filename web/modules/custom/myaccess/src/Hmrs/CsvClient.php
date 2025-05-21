<?php

declare(strict_types=1);

namespace Drupal\myaccess\Hmrs;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\myaccess\Exception\UserDataRetrievalException;
use Drupal\myaccess\Model\HmrsUserData;
use Drupal\myaccess\Model\HmrsUserRecord;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;

/**
 * Hmrs client that reads user data from a csv file.
 *
 * @package Drupal\myaccess\Hmrs
 */
class CsvClient implements ClientInterface {

  use HmrsTrait;

  /**
   * The Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * CsvClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config factory service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserData(string $email, bool $onlyPrimary = false): ?HmrsUserData {
    $data = [];

    try {
      $local_csv_path = $this->configFactory->get('myaccess.settings')
        ->get('hmrs.local_csv_path');

      $this->logger->info('Looking for user data with email "@email" using a datasource file in "@path".', [
        '@email' => $email,
        '@path' => $local_csv_path,
      ]);

      $csv = Reader::createFromPath($local_csv_path, 'r');
      $csv->setDelimiter(';');

      $records = $csv->getRecords();
      foreach ($records as $record) {
        if (strtolower($record[2]) === strtolower($email)) {
          //Check if it needs only the data for the primary position or not
          if(!$onlyPrimary || $record[5] == 'x') {
            $data[] = new HmrsUserRecord(
              $record[4],
              $record[6] == 'x',
              // todo: replace when the csv will be update.
              $record[7] == 'x',
              $record[8],
              $record[10],
              $record[11],
              $record[12],
              $record[13],
              $record[14],
              $record[15],
              $record[16],
              '',
              '',
              '',
              $record[17],
              $record[18],
              $record[19],
              $record[20],
              $record[21],
              $record[22],
              $record[23],
              $record[24],
              $record[25],
              $record[5] == 'x'
            );
          }
        }
      }
    }
    catch (\Exception $e) {
      throw new UserDataRetrievalException($email, 'csv');
    }

    if (!empty($data)) {
      return $this->buildUserData($data);
    }
    else {
      $this->logger->debug('Not found "@user" in "@path" file', [
        '@user' => $email,
        '@path' => $local_csv_path,
      ]);

      return NULL;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getAllHierarchy(): array {
    $data = [];
    $local_csv_path = $this->configFactory
      ->get('myaccess.settings')
      ->get('hmrs.local_csv_path');

    try {
      $csv = Reader::createFromPath($local_csv_path, 'r');
      $csv->setDelimiter(';');

      $records = $csv->getRecords();
      foreach ($records as $record) {
        // We use same mapping for compatibility with ApiClient function.
        $data[] = [
          'POS_POSGLOBALCODE' => $record[0],
          'POS_TITLE_LOCAL' => $record[5],
          'POS_TITLE_ENGLISH' => $record[5],
          'POS_COMPANYCODE' => $record[7],
          'POS_DIVISIONCODE' => $record[8],
          'POS_DEPARTMENTCODE' => $record[9],
          'POS_SUBAREACODE' => $record[10],
          'POS_SUBAREA2CODE' => $record[11],
          'POS_SUBAREA3CODE' => $record[12],
          'POS_SUBAREA4CODE' => $record[13],
          'POS_SUBAREA5CODE' => '',
          'POS_SUBAREA6CODE' => '',
          'POS_SUBAREA7CODE' => '',
          'POS_FUNCTIONCODE' => $record[14],
          'POS_SUBFUNCTIONCODE' => $record[15],
          'POS_LEGALENTITYCODE' => $record[16],
          'POS_REGIONCODE' => $record[17],
          'POS_COUNTRYCODE' => $record[18],
          'POS_LOCATIONCODE' => $record[20],
          'POS_FUNCTIONALAREA' => $record[21],
          'POS_AREACODE' => $record[22],
        ];
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Not possible retrieve the all hierarchy from "@path" file: %error.', [
        '@path' => $local_csv_path,
        '%error' => $e->getMessage(),
      ]);

      return $data;
    }
  }

}
