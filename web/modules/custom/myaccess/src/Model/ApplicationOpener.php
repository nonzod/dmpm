<?php

declare(strict_types=1);

namespace Drupal\myaccess\Model;

/**
 * Value object that store the data needed to open an application.
 *
 * Those data are used in the application-opener.html.twig template.
 */
class ApplicationOpener {

  const PLACEHOLDER_USERNAME = '$username';

  const PLACEHOLDER_PASSWORD = '$password';

  /**
   * The action url to post the form.
   *
   * @var string
   */
  private $formAction;

  /**
   * The url to forward the browser.
   *
   * @var string
   */
  private $postAction;

  /**
   * The url to forward the browser.
   *
   * @var string
   */
  private $myAccessUrl;

  /**
   * The name of the App.
   *
   * @var string
   */
  private $myAccessCode;

  /**
   * The parameters to post in the form.
   *
   * @var array
   */
  private $formParams;

  /**
   * Create a new instance from the Application data.
   *
   * @param \Drupal\myaccess\Model\MyAccessData $myAccessData
   *   The Application's MyAccess data.
   * @param string $username
   *   The user username.
   * @param string $password
   *   The user password in plain text.
   * @param bool $external
   *   TRUE if the user is accessing from outside the Menarini network.
   *
   * @return \Drupal\myaccess\Model\ApplicationOpener
   *   A new instance of this value object.
   */
  public static function fromApplication(
    MyAccessData $myAccessData,
    string $username,
    string $password,
    bool $external
  ): ApplicationOpener {
    $application_opener = new ApplicationOpener();

    $url = ($myAccessData->isExternalEnabled() && $external) ? $myAccessData->getExternalMyAccessUrl() : $myAccessData->getMyAccessUrl();

    $application_opener->postAction = '';
    if ($myAccessData->getPostAction() != '') {
      $application_opener->postAction = $url . $myAccessData->getPostAction();
    }

    $application_opener->formAction = '';
    if ($myAccessData->getFormAction() != '') {
      $application_opener->formAction = $url . $myAccessData->getFormAction();
    }

    $application_opener->myAccessUrl = $url;

    $application_opener->formParams = ApplicationOpener::mapFormParams(
      $myAccessData->getFormParams(),
      $username,
      $password
    );

    $application_opener->myAccessCode = '';
    if ($myAccessData->getMyAccessCode() != '') {
      $application_opener->myAccessCode = $myAccessData->getMyAccessCode();
    }

    return $application_opener;
  }

  /**
   * Map form parameters from string to array data.
   *
   * Replace username and password placeholders with real data.
   *
   * @param string $form_params_string
   *   The string used to create the form parameters array.
   * @param string $username
   *   The user username.
   * @param string $password
   *   The user password in plain text.
   *
   * @return array
   *   The form parameters as array data.
   */
  protected static function mapFormParams(string $form_params_string, string $username, string $password): array {

    // Convert form-params string to array.
    $form_params = json_decode($form_params_string, TRUE);

    if (!(is_array($form_params) && !empty($form_params))) {
      \Drupal::logger('myaccess')
        ->warning("Form-params string not converted to array");

      return [];
    }

    foreach ($form_params as &$form_param) {

      switch ($form_param['value']) {
        case ApplicationOpener::PLACEHOLDER_USERNAME:
          $form_param['value'] = $username;

          break;

        case ApplicationOpener::PLACEHOLDER_PASSWORD:
          $form_param['value'] = $password;

          break;
      }
    }

    return $form_params;
  }

  /**
   * Return the action url to post the form.
   *
   * @return string
   *   The action url to post the form.
   */
  public function getFormAction(): string {
    return $this->formAction;
  }

  /**
   * Return true if the action url to post the form is not empty.
   *
   * @return bool
   *   True if the action url to post the form is not empty.
   */
  public function hasFormAction(): bool {
    return $this->formAction != '';
  }

  /**
   * Return the url to forward the browser.
   *
   * @return string
   *   The url to forward the browser.
   */
  public function getPostAction(): string {
    return $this->postAction;
  }

  /**
   * Return true if the url to forward the browser is not empty.
   *
   * @return bool
   *   True if the url to forward the browser is not empty.
   */
  public function hasPostAction(): bool {
    return $this->postAction != '';
  }

  /**
   * Return the url to forward the browser.
   *
   * @return string
   *   The url to forward the browser.
   */
  public function getMyAccessUrl(): string {
    return $this->myAccessUrl;
  }

  /**
   * Return the name of the App.
   *
   * @return string
   *   The name of the App.
   */
  public function getMyAccessCode(): string {
    return $this->myAccessCode;
  }

  /**
   * Return the parameters to post in the form.
   *
   * @return array|null
   *   The parameters to post in the form.
   */
  public function getFormParams(): ?array {
    return $this->formParams;
  }

}
