<?php

namespace Drupal\myportal\Plugin\LanguageNegotiation;

use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies admin language from the default language of site.
 *
 * @LanguageNegotiation(
 *   id = Drupal\myportal\Plugin\LanguageNegotiation\LanguageNegotiationFixedAdmin::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   weight = -20,
 *   name = @Translation("Default language for administration pages"),
 *   description = @Translation("Default language of administration pages language setting.")
 * )
 * @package Drupal\myportal\Plugin\LanguageNegotiation
 */
class LanguageNegotiationFixedAdmin extends LanguageNegotiationUserAdmin {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-fixed-admin';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL): ?string {
    $langcode = NULL;
    // Fixed preference for backend.
    if (($this->currentUser->hasPermission('access administration pages') || $this->currentUser->hasPermission('view the administration theme')) && $this->isAdminPath($request)) {
      $langcode = $this->languageManager
        ->getDefaultLanguage()
        ->getId();
    }

    // Not an admin, no admin language preference or not on an admin path.
    return $langcode;
  }

}
