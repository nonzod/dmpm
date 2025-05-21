<?php

declare(strict_types=1);

namespace Drupal\myportal\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\myportal\MegaMenuManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for megamenu generate.
 */
class MegaMenuController extends ControllerBase {

  /**
   * The MegaMenu Manager service.
   *
   * @var \Drupal\myportal\MegaMenuManagerInterface
   */
  protected $megaMenuManager;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): MegaMenuController {
    $instance = parent::create($container);

    $mega_menu = $container->get('myportal.block_megamenu');
    assert($mega_menu instanceof MegaMenuManagerInterface);
    $instance->megaMenuManager = $mega_menu;

    $route_match = $container->get('current_route_match');
    assert($route_match instanceof RouteMatchInterface);
    $instance->routeMatch = $route_match;

    return $instance;
  }

  /**
   * Return the elements for Ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of elments for megamenu as Ajax response
   */
  public function generateMenu(): AjaxResponse {
    // I use routeMatch to retrieve the tid present in the url of the call.
    $current_parameters = $this->routeMatch->getParameters();
    $tid = $current_parameters->get('entity_id');

    $item_menu = $this->megaMenuManager->menuItems($tid);
    $block_menu_nid = $this->megaMenuManager->getBlockMenuNodeId($tid);
    if (!empty($block_menu_nid)) {
      if (!$this->megaMenuManager->viewBlockNode($tid)) {
        $block_menu_nid = NULL;
      }
    }

    return $this->buildResponse($item_menu, $block_menu_nid);
  }

  /**
   * Return the Ajax response for a megamenu block.
   *
   * @param array $items_menu
   *   The list of teaxonomy to return.
   * @param int|null $entity_block
   *   The entity id for render block.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The list of all taxonomy as Ajax response.
   */
  public function buildResponse(array $items_menu, $entity_block): AjaxResponse {

    // No cache info because Ajax responses are not cacheable.
    // @see https://www.drupal.org/project/drupal/issues/2701085
    $build = [
      '#theme' => 'block_megamenu',
      '#items_menu' => $items_menu,
      '#block_menu' => $entity_block,
    ];

    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand('#megamenu-wrapper', $build)
    );
    $response->addCommand(
      new InvokeCommand('#megamenu-wrapper', 'addClass', ['megamenu_visible'])
    );

    return $response;
  }

}
