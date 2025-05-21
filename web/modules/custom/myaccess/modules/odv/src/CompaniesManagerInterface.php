<?php

declare(strict_types=1);

namespace Drupal\odv;

/**
 * Provides an interface for Companies manager services.
 */
interface CompaniesManagerInterface {

  /**
   * Return the list of companies.
   *
   * @return array
   *   The list of companies.
   */
  public function getCompanies(): array;

  /**
   * Return the list of recipients for a company.
   *
   * @param string $companyName
   *   A company's name.
   *
   * @return array
   *   The list of recipients for a company.
   */
  public function getRecipientsForCompany(string $companyName): array;

}
