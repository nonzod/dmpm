<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

use Drupal\myaccess\OpenId\Client;

/**
 * Represent a single external application.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ExternalApplication {

  const SECONDS_SINCE_THE_UNIX_EPOCH = 'U';

  /**
   * The application id.
   *
   * @var int
   */
  private $id;

  /**
   * The application display name.
   *
   * @var string
   */
  private $displayName;

  /**
   * The application description.
   *
   * @var string
   */
  private $description;

  /**
   * The application image url.
   *
   * @var string
   */
  private $imageUrl;

  /**
   * The application url.
   *
   * @var string
   */
  private $url;

  /**
   * The application code.
   *
   * @var string
   */
  private $code;

  /**
   * The application visibility.
   *
   * @var string
   */
  private $visibility;

  /**
   * The application delegable.
   *
   * @var bool
   */
  private $delegable;

  /**
   * The application sso enabled.
   *
   * @var bool
   */
  private $ssoEnabled;

  /**
   * The application offline.
   *
   * @var bool
   */
  private $offline;

  /**
   * The application redirect url.
   *
   * @var string
   */
  private $redirectUrl;

  /**
   * The application owner.
   *
   * @var string
   */
  private $owner;

  /**
   * The application last modified by.
   *
   * @var string
   */
  private $lastModifiedBy;

  /**
   * The application last modified date.
   *
   * @var string
   */
  private $lastModifiedDate;

  /**
   * The application default application.
   *
   * @var bool
   */
  private $defaultApplication;

  /**
   * The application auth type.
   *
   * @var string
   */
  private $applicationAuthType;

  /**
   * The application client type.
   *
   * @var string
   */
  private $applicationClientType;

  /**
   * The application cmdb id.
   *
   * @var string
   */
  private $cmdbId;

  /**
   * The application myaccess url.
   *
   * @var string
   */
  private $myAccessUrl;

  /**
   * The application external myaccess url.
   *
   * @var string
   */
  private $externalMyAccessUrl;

  /**
   * ExternalApplication constructor.
   */
  final private function __construct() {
  }

  /**
   * Create a new array of ExternalApplication from data array.
   *
   * @param array $data
   *   An array with external applications data.
   *
   * @return \Drupal\myaccess\Model\ExternalApplication[]
   *   A new array of ExternalApplication from data array.
   */
  final public static function fromArray(array $data): array {
    $applications = [];

    foreach ($data as $app) {
      $application = new ExternalApplication();
      $application->id = $app['id'];
      $application->displayName = $app['displayName'];
      $application->description = !empty($app['description']) ? $app['description'] : '';
      $application->imageUrl = $app['imageUrl'];
      $application->url = $app['url'];
      $application->code = $app['code'];
      $application->visibility = $app['visibility'];
      $application->delegable = $app['delegable'];
      $application->ssoEnabled = $app['ssoEnabled'];
      $application->offline = $app['offline'];
      $application->redirectUrl = $app['redirectUrl'];
      $application->owner = $app['owner'];
      $application->lastModifiedBy = $app['lastModifiedBy'];
      $application->lastModifiedDate = ExternalApplication::rfc3339ToTimestamp($app['lastModifiedDate']);
      $application->defaultApplication = $app['defaultApplication'];
      $application->applicationAuthType = $app['applicationAuthType'];
      $application->applicationClientType = $app['applicationClientType'];
      $application->cmdbId = $app['cmdbId'];

      $client = \Drupal::service('myaccess.oidc_client');
      /** @var \Drupal\myaccess\Model\MyAccessData $app_additional_data */
      $app_additional_data = $client->getMyAccessData($app['code']);
      $application->myAccessUrl = $app_additional_data->getMyAccessUrl();
      $application->externalMyAccessUrl = $app_additional_data->getExternalMyAccessUrl();

      $applications[] = $application;
    }

    return $applications;
  }

  /**
   * Return the application id.
   *
   * @return int
   *   The application id.
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * Return the application display name.
   *
   * @return string
   *   The application display name.
   */
  public function getDisplayName(): string {
    return $this->displayName;
  }

  /**
   * Return the application description.
   *
   * @return string|null
   *   The application description.
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * Return the application image url.
   *
   * @return string
   *   The application image url.
   */
  public function getImageUrl(): string {
    return $this->imageUrl;
  }

  /**
   * Return the application url.
   *
   * @return string
   *   The application url.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Return the application code.
   *
   * @return string
   *   The application code.
   */
  public function getCode(): string {
    return $this->code;
  }

  /**
   * Return the application visibility.
   *
   * @return string
   *   The application visibility.
   */
  public function getVisibility(): string {
    return $this->visibility;
  }

  /**
   * Return the application delegable.
   *
   * @return bool
   *   The application delegable.
   */
  public function isDelegable(): bool {
    return $this->delegable;
  }

  /**
   * Return the application sso enabled.
   *
   * @return bool
   *   The application sso enabled.
   */
  public function hasSsoEnabled(): bool {
    return $this->ssoEnabled;
  }

  /**
   * Return the application offline.
   *
   * @return bool
   *   The application offline.
   */
  public function hasOffline(): bool {
    return $this->offline;
  }

  /**
   * Return the application redirect url.
   *
   * @return string
   *   The application redirect url.
   */
  public function getRedirectUrl(): string {
    return $this->redirectUrl;
  }

  /**
   * Return the application owner.
   *
   * @return string
   *   The application owner.
   */
  public function getOwner(): string {
    return $this->owner;
  }

  /**
   * Return the application last modified by.
   *
   * @return string
   *   The application last modified by.
   */
  public function getLastModifiedBy(): string {
    return $this->lastModifiedBy;
  }

  /**
   * Return the application last modified date.
   *
   * @return string
   *   The application last modified date.
   */
  public function getLastModifiedDate(): string {
    return $this->lastModifiedDate;
  }

  /**
   * Return the application default application.
   *
   * @return bool
   *   The application default application.
   */
  public function isDefaultApplication(): bool {
    return $this->defaultApplication;
  }

  /**
   * Return the application auth type.
   *
   * @return string
   *   The application auth type.
   */
  public function getApplicationAuthType(): string {
    return $this->applicationAuthType;
  }

  /**
   * Return the application client type.
   *
   * @return string
   *   The application client type.
   */
  public function getApplicationClientType(): string {
    return $this->applicationClientType;
  }

  /**
   * Return the application cmdb id.
   *
   * @return string
   *   The application cmdb id.
   */
  public function getCmdbId(): string {
    return $this->cmdbId;
  }

  /**
   * Build the settings array.
   *
   * @return string
   *   A serialized array.
   */
  public function getSettings(): string {
    return serialize(
      [
        'code' => $this->code,
        'visibility' => $this->visibility,
        'external' => TRUE,
        'auth_type' => $this->applicationAuthType,
        'client_type' => $this->applicationClientType,
        'myaccess_url' => $this->myAccessUrl,
        'myaccess_external_url' => $this->externalMyAccessUrl,
      ]);
  }

  /**
   * Convert a RFC3339 date to an Unix timestamp.
   *
   * @param string $date
   *   The RFC3339 date.
   *
   * @return string
   *   A Unix timestamp.
   */
  public static function rfc3339ToTimestamp(string $date): string {
    return \DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $date)
      ->format(self::SECONDS_SINCE_THE_UNIX_EPOCH);
  }

}
