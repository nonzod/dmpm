<?php

declare(strict_types=1);

namespace Drupal\myaccess\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\myaccess\CookieTrait;
use Drupal\myaccess\Entity\ApplicationInterface;
use Drupal\myaccess\Model\ApplicationOpener;
use Drupal\myaccess\OpenId\ClientInterface;
use Drupal\myaccess\UncacheableRedirectTrait;
use Drupal\myaccess\UserManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides route responses to open an application.
 */
class ApplicationOpenerController extends ControllerBase {

  use UncacheableRedirectTrait;
  use CookieTrait;

  /**
   * The User Manager service.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  private $userManager;

  /**
   * The OpenId client service.
   *
   * @var \Drupal\myaccess\OpenId\Client|\Drupal\myaccess\OpenId\ClientInterface
   */
  private $client;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container
  ): ApplicationOpenerController {
    $instance = parent::create($container);

    $user_manager = $container->get('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);
    $instance->userManager = $user_manager;

    $client = $container->get('myaccess.oidc_client');
    assert($client instanceof ClientInterface);
    $instance->client = $client;

    $renderer = $container->get('renderer');
    assert($renderer instanceof RendererInterface);
    $instance->renderer = $renderer;

    $logger = $container->get('logger.channel.myaccess');
    assert($logger instanceof LoggerInterface);
    $instance->logger = $logger;

    return $instance;
  }

  /**
   * Open an application in a new tab (after check for access).
   *
   * @param \Drupal\myaccess\Entity\ApplicationInterface $application
   *   The application to open.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A page with the code to perform the redirect.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function open(ApplicationInterface $application): Response {
    if (!$application->access('view')) {
      throw new AccessDeniedHttpException();
    }

    // Variables required.
    $username = $this->userManager->getUsername();
    $password = $this->userManager->getPassword();
    $external = $this->userManager->isExternal();
    $check_access_external = $this->userManager->checkAccessExternal();
    $application_type = $application->getType();
    $application_visibility = $application->getVisibility();
    $access_permitted = TRUE;

    // If user isn't in VPN, can't access applications from external network
    // and application is 'private' can't access to application.
    // @see https://wellnet.atlassian.net/browse/MEN-778.
    // @see template_preprocess_application().
    if ($application_type == 'remote' && $application_visibility == 'private'
      && $external && !$check_access_external) {
      $access_permitted = FALSE;
    }

    $this->logger->info('User @user (@check_access_external) request to open application "@app" (@type/@visibility) from @net network: @access_permitted access to application.',
      [
        '@user' => $username,
        '@app' => $application->label() ?? '<no name>',
        '@type' => $application_type,
        '@visibility' => $application_visibility,
        '@net' => $external ? 'outside' : 'inside',
        '@check_access_external' => $check_access_external ? 'access external approved' : 'access external not approved',
        '@access_permitted' => $access_permitted ? 'approved' : 'denied',
      ]
    );

    if (!$access_permitted) {
      throw new AccessDeniedHttpException();
    }

    try {
      $code = $application->getSettings()['code'];
      if ($code == NULL || $code == '') {
        throw new \Exception('Application code is null');
      }

      $my_access_data = $this->client->getMyAccessData($code);

      $application_opener =
        ApplicationOpener::fromApplication(
          $my_access_data,
          $username,
          $password,
          $external
        );

      $build = [
        '#theme' => 'application_opener',
        '#application' => $application_opener,
      ];
      $content = $this->renderer->render($build);
      $response = new Response($content);

      if ($external) {
        $response = $this->withJwtCookies($response, $username, $password);
      }

      return $response;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong opening the application, please try again later.'));

      $this->logger->error('Something went wrong opening the application "@app": @message.', [
        '@app' => $application->label() ?? 'no-name',
        '@message' => $e->getMessage(),
      ]);

      return $this->redirect('<front>');
    }
  }

}
