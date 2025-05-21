<?php

declare(strict_types=1);

namespace Drupal\hmrs_mocks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\File\FileSystemInterface;

/**
 * Provides route responses for applications endpoints.
 */
class HmrsController extends ControllerBase {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleList;

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container
  ): HmrsController {
    $instance = parent::create($container);

    $logger = $container->get('logger.channel.hmrs_mocks');
    assert($logger instanceof LoggerInterface);
    $instance->logger = $logger;

    $fileSystem = $container->get('file_system');
    assert($fileSystem instanceof FileSystemInterface);
    $instance->fileSystem = $fileSystem;

    $instance->moduleList = $container->get('extension.list.module');

    return $instance;
  }

  /**
   * Get the token mocks.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return the token mocks.
   */
  public function getToken(): JsonResponse {
    $dummy_token = [
      'id_token' => "eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJwempfVnVpZml1TEhRQVdRNHVxN1pMRFRZSUhsX2tWU0MydkpEaHZVdFpFIn0.eyJleHAiOjE2MDc1MTI5NzcsImlhdCI6MTYwNzUxMjkxNywianRpIjoiZmI2YjU4ZDktM2FjYS00M2MwLWEwMmYtZWNlY2NjYTRiNGMyIiwiaXNzIjoiaHR0cHM6Ly9rZXljbG9hay1xYS5tZW5hcmluaS5uZXQvYXV0aC9yZWFsbXMvbWVuYXJpbmkiLCJzdWIiOiIwMjcxOTEwMS1iODhlLTRjYzgtODJiNi0wMDY4Y2RlM2UyZmMiLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJtZy1hcHBfTWVuYXJpbmkgSW50cmFuZXQgUG9ydGFsIiwic2Vzc2lvbl9zdGF0ZSI6IjMzNDU1MWIxLWVjMmEtNGU2Yi05ZGJjLTFkMmI3ODQ1ZWRjMCIsImFjciI6IjEiLCJzY29wZSI6InByb2ZpbGUgY2xpZW50LWFkbWluLXJvbGUgZW1haWwiLCJyZXNvdXJjZV9hY2Nlc3MiOnt9LCJlbWFpbF92ZXJpZmllZCI6ZmFsc2UsImNsaWVudEhvc3QiOiIxMC4xMDAuMTYuMTQxIiwiY2xpZW50SWQiOiJtZy1hcHBfTWVuYXJpbmkgSW50cmFuZXQgUG9ydGFsIiwicm9sZXMiOlsib2ZmbGluZV9hY2Nlc3MiLCJST0xFX0FETUlOIiwidW1hX2F1dGhvcml6YXRpb24iXSwicHJlZmVycmVkX3VzZXJuYW1lIjoic2VydmljZS1hY2NvdW50LW1nLWFwcF9tZW5hcmluaSBpbnRyYW5ldCBwb3J0YWwiLCJjbGllbnRBZGRyZXNzIjoiMTAuMTAwLjE2LjE0MSIsImF1dGhvcml0aWVzIjpbIm9mZmxpbmVfYWNjZXNzIiwiUk9MRV9BRE1JTiIsInVtYV9hdXRob3JpemF0aW9uIl19.g-H2rCfGKRrI43VrIZenNvI9tTf-PRrdSRTfQY7LTAlKdSmm_jiZRD_p4Ops9nqBBk67QUpMmE2EqPd-iHoWgj4Zj5MHyo6a-ogCFJlX1DNWmQN42wiJCot3D48eMk8Y8GV4-ANmymF7DAW_hRWV0ja04lc_zOzEUrbb3jsD4Qf9o98WhJkKssNje5EOOJAshED6ILQVyMpmDdpHCSx0VfU92m3HqCymxPw_l5VBMsanG7BlBfVtxBHaw3yH6VcQERdIqrynL-8Vp_VXY7nLuZQGfKtrNGTG6GdER7xCImTzrOriECc84XgbohMCpOVV_K5_N-zbxjE32P4gdMVAWQ",
    ];

    return new JsonResponse($dummy_token, 200);
  }

  /**
   * Check if the user exists.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return the position user, json mocks.
   */
  public function getPositionByEmail(Request $request): Response {
    $path = $this->fileSystem->realpath(
      $this->moduleList->getPath('hmrs_mocks')
    );

    if (!empty($path)) {
      $position_email = $path . '/mocks/positions-email.json';
      $this->logger->debug('Load mocked hmrs from ' . $position_email);

      if (file_exists($position_email)) {
        return new Response(file_get_contents($position_email), 200);
      }
    }

    return new Response('', 200);
  }

  /**
   * Shows all positions exposed by the endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return all position user, json mocks.
   */
  public function getAllHierarchy(): Response {
    $path = $this->fileSystem->realpath(
      $this->moduleList->getPath('hmrs_mocks')
    );

    if (!empty($path)) {
      $all_position = $path . '/mocks/all-hierarchy.json';
      $this->logger->debug('Load mocked hmrs from ' . $all_position);

      if (file_exists($all_position)) {
        return new Response(file_get_contents($all_position), 200);
      }
    }

    return new Response('', 200);
  }

  /**
   * Shows a user's locations filtered by global code.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return the position user by globalcode, json mocks.
   */
  public function getHierarchyByPosition(Request $request): Response {
    $path = $this->fileSystem->realpath(
      $this->moduleList->getPath('hmrs_mocks')
    );

    if (!empty($path)) {
      $hierarchy_position = $path . '/mocks/hierarchy-position.json';
      $this->logger->debug('Load mocked hmrs from ' . $hierarchy_position);

      if (file_exists($hierarchy_position)) {
        return new Response(file_get_contents($hierarchy_position), 200);
      }
    }

    return new Response('', 200);
  }

  /**
   * {@inheritDoc}
   */
  public function hmrsEndpoint(Request $request) {
    $wsName = $request->get('WsName');
    $services = '';
    if (!empty($wsName)) {
      switch ($wsName) {
        case 'ALL_HIERARCHY':
          $services = $this->getAllHierarchy();

          break;

        case 'POSITIONS_BY_MAIL':
          $services = $this->getPositionByEmail($request);

          break;

        case 'HIERARCHY_BY_POSITION':
          $services = $this->getHierarchyByPosition($request);

          break;
      }
    }

    return $services;
  }

}
