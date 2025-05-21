<?php

declare(strict_types=1);

namespace Drupal\odv;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Read companies data from config.
 */
class ConfigurationCompaniesManager implements CompaniesManagerInterface {

  /**
   * Companies' configuration.
   *
   * @var array
   */
  private array $companies;

  /**
   * CompaniesManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->companies = $configFactory->get('odv.settings')->get('companies');
  }

  /**
   * {@inheritdoc}
   */
  public function getCompanies(): array {
    $companies = array_map(function (array $element) {
      return $element['name'];
    }, $this->companies);

    return array_combine($companies, $companies);
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientsForCompany(?string $companyName = NULL): array {
    if ($companyName == NULL) {
      return [];
    }

    foreach ($this->companies as $company) {
      if ($company['name'] == $companyName) {
        return array_combine($company['recipients'], $company['recipients']);
      }
    }

    return [];
  }

}
