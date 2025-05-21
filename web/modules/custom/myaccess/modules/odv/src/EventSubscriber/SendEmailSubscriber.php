<?php

declare(strict_types=1);

namespace Drupal\odv\EventSubscriber;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\odv\Event\ODVEvents;
use Drupal\odv\Event\ODVSubmitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for update user data from HMRS.
 */
class SendEmailSubscriber implements EventSubscriberInterface {

  const NEW_SUBMISSION = 'new_submission';

  /**
   * The Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * The Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private MailManagerInterface $mailManager;

  /**
   * SendEmailSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The Current user service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The Mail manager service.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    MailManagerInterface $mail_manager
  ) {
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[ODVEvents::SUBMIT][] = ['sendEmail'];

    return $events;
  }

  /**
   * Send email in response to submit events.
   *
   * @param \Drupal\odv\Event\ODVSubmitEvent $event
   *   An event for ODV submission.
   */
  public function sendEmail(ODVSubmitEvent $event): void {
    $submission = $event->getSubmission();

    $this->mailManager->mail(
      'odv',
      'new_submission',
      $submission->getRecipient(),
      $this->currentUser->getPreferredLangcode(),
      ['submission' => $submission]
    );
  }

}
