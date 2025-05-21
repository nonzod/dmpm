<?php

declare(strict_types=1);

namespace Drupal\myaccess\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\flag\Controller\ActionLinkController;
use Drupal\flag\FlagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class CustomActionLinkController, used to block the flag after 10 items.
 */
class CustomActionLinkController extends ActionLinkController {

  use StringTranslationTrait;

  const FAVORITE_APPLICATION = 'favorite_application';

  /**
   * The flag count service.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  private $flagCount;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container
  ): CustomActionLinkController {
    $instance = parent::create($container);

    $flag_count = $container->get('flag.count');
    assert($flag_count instanceof FlagCountManagerInterface);
    $instance->flagCount = $flag_count;

    $account = $container->get('current_user');
    assert($account instanceof AccountInterface);
    $instance->account = $account;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function flag(FlagInterface $flag, $entity_id) {
    /** @var \Drupal\myaccess\Entity\Application $entity */
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->flagService->getFlagById(CustomActionLinkController::FAVORITE_APPLICATION);
    $flag_user = $this->flagCount->getUserFlagFlaggingCount($flag, $this->account);

    if ($flag_user == ApplicationController::MAX_FAVORITE_APPLICATIONS_NUMBER) {
      $flag->setFlagMessage($this->t("The limit of apps to be included in the favorites has been exceeded."));

      return $this->generateResponse($flag, $entity, $flag->getMessage('flag'));
    }

    try {
      $this->flagService->flag($flag, $entity);
    }
    catch (\LogicException $e) {
      // Fail silently, so we return to the entity, which will show an updated
      // link for the existing state of the flag.
    }

    return $this->generateResponse($flag, $entity, $flag->getMessage('flag'));
  }

  /**
   * Generates a response after the flag has been updated.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   (optional) The message to flash.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   */
  protected function generateResponse(FlagInterface $flag, EntityInterface $entity, $message) {
    $response = new AjaxResponse();

    // Get the link type plugin.
    $link_type = $flag->getLinkTypePlugin();

    // Generate the link render array.
    $link = $link_type->getAsFlagLink($flag, $entity);

    // Generate a CSS selector to use in a JQuery Replace command.
    $selector = '.js-flag-' . Html::cleanCssIdentifier((string) $flag->id()) . '-' . $entity->id();

    // Create a new JQuery Replace command to update the link display.
    $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($link));
    $response->addCommand($replace);

    // Push a message pulsing command onto the stack.
    $pulse = new ActionLinkFlashCommand($selector, (string) $message);
    $response->addCommand($pulse);

    return $response;
  }

}
