<?php

namespace Drupal\myaccess\EventSubscriber\StressTests;

use Drupal\user\UserInterface;
use League\Csv\Reader;
use Drupal\myaccess\EventSubscriber\UpdateUserDataSubscriber as OriginalUpdateUserDataSubscriber;

/**
 * Defines the UpdateUserDataSubscriber class.
 *
 * @package Drupal\myaccess\EventSubscriber\StressTests
 */
class UpdateUserDataSubscriber extends OriginalUpdateUserDataSubscriber {

  /**
   * Retrieve an alternative mail.
   *
   * @param string $original_mail
   *   The original mail.
   *
   * @return string|null
   *   The new mail.
   */
  protected function getAlternativeMail(string $original_mail) {
    try {
      $local_csv_path = \Drupal::config('myaccess.settings')
        ->get('hmrs.local_csv_path');
      $mails = $this->readFileCsv($local_csv_path);

      // Use the number of mail test for retrieve the row with new mail from
      // csv file.
      preg_match_all('/^tmyportal(\d+)@cloud.menarini.com$/', $original_mail, $matches);
      if (isset($matches[1][0]) && is_numeric($matches[1][0])) {
        $index = (int) $matches[1][0] + 10;

        return $mails[$index];
      }

      return $mails[array_rand($mails)];
    }
    catch (\Throwable $exception) {
      $this->logger->warning($exception->getMessage());
    }

    return NULL;
  }

  /**
   * Read file and convert to array.
   *
   * @param string $csv_path
   *   The csv path.
   *
   * @return array
   *   An array of mail.
   *
   * @throws \League\Csv\InvalidArgument
   */
  private function readFileCsv(string $csv_path) {
    $cid = 'myaccess:data:' . __CLASS__;
    if ($cache = \Drupal::cache()->get($cid)) {
      $mails = $cache->data;
    }
    else {
      $mails = [];
      $csv = Reader::createFromPath($csv_path, 'r');
      $csv->setDelimiter(';');
      $records = $csv->getRecords();
      foreach ($records as $record) {
        $mails[] = $record[2];
      }
      $mails = array_filter($mails);
      \Drupal::cache()->set($cid, $mails);
    }

    return $mails;
  }

  /**
   * {@inheritDoc}
   *
   * If user use a test mail, will change with a real user mail for retrieve
   * user data from HRMS.
   */
  protected function retrieveAndUpdateUserData(UserInterface $user) {
    $email = $user->getEmail();

    // Cannot proceed without an email address.
    if (!$email) {

      return;
    }

    // Example mail: "tmyportal99@cloud.menarini.com".
    if (!preg_match('/^tmyportal(\d+)@cloud.menarini.com$/', $email)) {
      parent::retrieveAndUpdateUserData($user);

      return;
    }

    // Set an alternative mail to use for retrieve the user data.
    $new_mail = $this->getAlternativeMail($email);
    if (empty($new_mail)) {
      parent::retrieveAndUpdateUserData($user);

      return;
    }

    $this->logger->debug(sprintf('Change mail used for retrieve user data: %s to %s', $email, $new_mail));
    $this->logger->debug(sprintf('Retrieve user data with %s client', get_class($this->client)));

    // Extract the user data from the HMRS system.
    $userData = $this->client->getUserData($new_mail);

    // Update the user profile.
    $this->userManager->updateData($user, $userData);

    $this->logger->info('Updated userData for current user (@user).', ['@user' => $user->getAccountName()]);
  }

}
