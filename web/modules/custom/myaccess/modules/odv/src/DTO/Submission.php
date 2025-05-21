<?php

declare(strict_types=1);

namespace Drupal\odv\DTO;

/**
 * DTO for a user submission.
 */
class Submission {

  /**
   * The selected company.
   *
   * @var string
   */
  private string $company;

  /**
   * The message recipient.
   *
   * @var string
   */
  private string $recipient;

  /**
   * The message subject.
   *
   * @var string
   */
  private string $subject;

  /**
   * The message body.
   *
   * @var string
   */
  private string $body;

  /**
   * Documents attached to the request.
   *
   * @var \SplFileInfo[]
   */
  private array $attachments;

  /**
   * TRUE if the uses has asked to remain anonymous.
   *
   * @var bool
   */
  private bool $anonymous;

  /**
   * TRUE if the user has accepted the terms and conditions.
   *
   * @var bool
   */
  private bool $termsAccepted;

  /**
   * The message sender's email.
   *
   * @var string
   */
  private string $senderEmail;

  /**
   * Submission DTO constructor.
   *
   * @param string $company
   *   The selected company.
   * @param string $recipient
   *   The message recipient.
   * @param string $subject
   *   The message subject.
   * @param string $body
   *   The message body.
   * @param \SplFileInfo[] $attachments
   *   Documents attached to the request.
   * @param bool $anonymous
   *   TRUE if the uses has asked to remain anonymous.
   * @param bool $termsAccepted
   *   TRUE if the user has accepted the terms and conditions.
   * @param string $senderEmail
   *   The message sender.
   */
  public function __construct(
    string $company,
    string $recipient,
    string $subject,
    string $body,
    array $attachments,
    bool $anonymous,
    bool $termsAccepted,
    string $senderEmail
  ) {
    $this->company = $company;
    $this->recipient = $recipient;
    $this->subject = $subject;
    $this->body = $body;
    $this->attachments = $attachments;
    $this->anonymous = $anonymous;
    $this->termsAccepted = $termsAccepted;
    $this->senderEmail = $senderEmail;
  }

  /**
   * Return the selected company.
   *
   * @return string
   *   The selected company.
   */
  public function getCompany(): string {
    return $this->company;
  }

  /**
   * Return the message recipient.
   *
   * @return string
   *   The message recipient.
   */
  public function getRecipient(): string {
    return $this->recipient;
  }

  /**
   * Return the message subject.
   *
   * @return string
   *   The message subject.
   */
  public function getSubject(): string {
    return $this->subject;
  }

  /**
   * Return the message body.
   *
   * @return string
   *   The message body.
   */
  public function getBody(): string {
    return $this->body;
  }

  /**
   * Return documents attached to the request.
   *
   * @return \SplFileInfo[]
   *   Documents attached to the request.
   */
  public function getAttachments(): array {
    return $this->attachments;
  }

  /**
   * Return TRUE if the uses has asked to remain anonymous.
   *
   * @return bool
   *   TRUE if the uses has asked to remain anonymous.
   */
  public function isAnonymous(): bool {
    return $this->anonymous;
  }

  /**
   * Return TRUE if the user has accepted the terms and conditions.
   *
   * @return bool
   *   TRUE if the user has accepted the terms and conditions.
   */
  public function isTermsAccepted(): bool {
    return $this->termsAccepted;
  }

  /**
   * Return the message sender's email.
   *
   * @return string
   *   The message sender's email.
   */
  public function getSenderEmail(): string {
    return $this->senderEmail;
  }

}
