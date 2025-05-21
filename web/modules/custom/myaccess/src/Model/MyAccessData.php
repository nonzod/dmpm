<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

/**
 * Represent MyAccess data.
 */
class MyAccessData {

  /**
   * The application enabled.
   *
   * @var bool
   */
  private $enabled;

  /**
   * The application myaccess code.
   *
   * @var string
   */
  private $myAccessCode;

  /**
   * The application myaccess url.
   *
   * @var string
   */
  private $myAccessUrl;

  /**
   * The application external myaccess code.
   *
   * @var string
   */
  private $externalMyAccessCode;

  /**
   * The application external enabled.
   *
   * @var bool
   */
  private $externalEnabled;

  /**
   * The application external myaccess url.
   *
   * @var string
   */
  private $externalMyAccessUrl;

  /**
   * The application from action.
   *
   * @var string
   */
  private $formAction;

  /**
   * The application post action.
   *
   * @var string
   */
  private $postAction;

  /**
   * The application form params.
   *
   * @var string
   */
  private $formParams;

  /**
   * ExternalApplication constructor.
   */
  final private function __construct() {
  }

  /**
   * Return a new MyAccessData with data from MyAccess.
   *
   * @param array $data
   *   An array with MyAccess data.
   *
   * @return \Drupal\myaccess\Model\MyAccessData
   *   A ew MyAccessData with data from MyAccess.
   */
  final public static function fromArray(array $data): MyAccessData {
    $my_access_data = new MyAccessData();

    $my_access_data->enabled = $data['enabled'] ?? FALSE;
    $my_access_data->myAccessCode = $data['myAccessCode'] ?? '';
    $my_access_data->myAccessUrl = $data['myAccessUrl'] ?? '';
    $my_access_data->externalMyAccessCode = $data['externalMyAccessCode'] ?? '';
    $my_access_data->externalEnabled = $data['externalEnabled'] ?? FALSE;
    $my_access_data->externalMyAccessUrl = $data['externalMyAccessUrl'] ?? '';
    $my_access_data->formAction = $data['formAction'] ?? '';
    $my_access_data->postAction = $data['postAction'] ?? '';
    $my_access_data->formParams = $data['formParams'] ?? '';

    return $my_access_data;
  }

  /**
   * Return the application enabled.
   *
   * @return bool
   *   The application enabled.
   */
  public function isEnabled(): bool {
    return $this->enabled;
  }

  /**
   * Return the application myaccess code.
   *
   * @return string
   *   The application myaccess code.
   */
  public function getMyAccessCode(): string {
    return $this->myAccessCode;
  }

  /**
   * Return the application myaccess url.
   *
   * @return string
   *   The application myaccess url.
   */
  public function getMyAccessUrl(): string {
    return $this->myAccessUrl;
  }

  /**
   * Return the application external myaccess code.
   *
   * @return string
   *   The application external myaccess code.
   */
  public function getExternalMyAccessCode(): string {
    return $this->externalMyAccessCode;
  }

  /**
   * Return the application external enabled.
   *
   * @return bool
   *   The application external enabled.
   */
  public function isExternalEnabled(): bool {
    return $this->externalEnabled;
  }

  /**
   * Return the application external myaccess url.
   *
   * @return string
   *   The application external myaccess url.
   */
  public function getExternalMyAccessUrl(): string {
    return $this->externalMyAccessUrl;
  }

  /**
   * Return the application from action.
   *
   * @return string
   *   The application from action.
   */
  public function getFormAction(): string {
    return $this->formAction;
  }

  /**
   * Return the application post action.
   *
   * @return string
   *   The application post action.
   */
  public function getPostAction(): string {
    return $this->postAction;
  }

  /**
   * Return the application form params.
   *
   * @return string
   *   The application form params.
   */
  public function getFormParams(): string {
    return $this->formParams;
  }

}
