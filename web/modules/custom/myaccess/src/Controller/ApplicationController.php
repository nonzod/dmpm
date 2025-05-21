<?php

declare(strict_types=1);

namespace Drupal\myaccess\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\myaccess\ApplicationsManagerInterface;
use Drupal\myaccess\UncacheableRedirectTrait;
use Drupal\myaccess\UserManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for applications endpoints.
 */
class ApplicationController extends ControllerBase {

  use UncacheableRedirectTrait;

  const MAX_FAVORITE_APPLICATIONS_NUMBER = 10;

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  private $userManager;

  /**
   * The Applications Manager service.
   *
   * @var \Drupal\myaccess\ApplicationsManagerInterface
   */
  private $applicationsManager;

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container
  ): ApplicationController {
    $instance = parent::create($container);

    $user_manager = $container->get('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);
    $instance->userManager = $user_manager;

    $applications_manager = $container->get('myaccess.applications_manager');
    assert($applications_manager instanceof ApplicationsManagerInterface);
    $instance->applicationsManager = $applications_manager;

    return $instance;
  }

  /**
   * Return the list of favorite applications as Ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of favorite applications as Ajax response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function favorite(): AjaxResponse {
    $applications = $this->applicationsManager->getFavoriteApplicationIds();
    $sorted_application_ids = $this->applicationsManager->getSortedFavoriteApplicationIds();

    // No cache info because Ajax responses are not cacheable.
    // @see https://www.drupal.org/project/drupal/issues/2701085
    $build = [
      '#theme' => 'favorites',
      '#applications' => $sorted_application_ids ?? $applications,
      '#placeholders' => self::MAX_FAVORITE_APPLICATIONS_NUMBER,
    ];

    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand('#applications-favorite-wrapper', $build)
    );

    return $response;
  }

  /**
   * Sort your favorite apps after moving them on the home screen.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  public function sortFavoriteApplications(Request $request): void {
    $application_ids = $request->request->get('application_ids');
    $this->applicationsManager->setSortedFavoriteApplicationIds($application_ids);
  }

  /**
   * Return the list of all applications as Ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of all applications as Ajax response.
   */
  public function grid(): AjaxResponse {
    $user = $this->userManager->getCurrentDrupalUser();

    $user_applications = $this->applicationsManager->getUserApplications($user);
    $local_applications = $this->applicationsManager->getLocalApplications();
    $applications = array_merge($user_applications, $local_applications);

    return $this->buildGridResponse($applications, $this->t('My App'), 'myapp');
  }

  /**
   * Return the list of all applications as Ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of all applications as Ajax response.
   */
  public function google(): AjaxResponse {
    $applications = $this->applicationsManager->getGoogleApplications();

    return $this->buildGridResponse($applications, $this->t('Google App'), 'google');
  }

  /**
   * Return the list of all applications as Ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of all applications as Ajax response.
   */
  public function myLinks(): AjaxResponse {
    $user = $this->userManager->getCurrentDrupalUser();

    $applications = $this->applicationsManager->getMyLinksApplications($user);

    return $this->buildGridResponse($applications, $this->t('My Links'), 'mylinks');
  }

  /**
   * Return an Ajax response for an application grid.
   *
   * @param int[] $applications
   *   The list of applications to return.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The block title.
   * @param string $type
   *   Used for tab active in lightbox.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of all applications as Ajax response.
   */
  public function buildGridResponse(
    array $applications,
    TranslatableMarkup $title,
    $type
  ): AjaxResponse {
    $settings = ['myaccess' => ['applications' => []]];
    foreach ($applications as $application) {
      $settings['myaccess']['applications'][] =
        $this->applicationsManager->toSearchArray($application);
    }

    // No cache info because Ajax responses are not cacheable.
    // @see https://www.drupal.org/project/drupal/issues/2701085
    $build = [
      '#theme' => 'grid',
      '#applications' => $applications,
      '#title' => $title,
      '#type' => $type,
      '#attached' => [
        'library' => [
          'myaccess/filter',
        ],
        'drupalSettings' => $settings,
      ],
    ];

    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand('#applications-grid-wrapper', $build)
    );
    $response->addCommand(
      new CssCommand('#applications-grid-wrapper', ['display' => 'block'])
    );

    return $response;
  }

}
