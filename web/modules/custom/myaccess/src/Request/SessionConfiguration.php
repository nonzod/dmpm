<?php

namespace Drupal\myaccess\Request;

use Drupal\myaccess\StackMiddleware\IsExternalMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\SessionConfiguration as DrupalSessionConfiguration;

/**
 * Provide a custom session configuration generator.
 *
 * This class is needed because the `gc_maxlifetime` option has to be different
 * between internal and external access.
 */
class SessionConfiguration extends DrupalSessionConfiguration {

  private const EXTERNAL_GC_MAXLIFETIME_IN_SECONDS = 1800;

  private const INTERNAL_GC_MAXLIFETIME_IN_SECONDS = 28800;

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = parent::getOptions($request);

    // See \Drupal\myaccess\StackMiddleware\IsExternalMiddleware::handle().
    $external = $request->attributes->get(IsExternalMiddleware::KEY, TRUE);

    if ($external) {
      $options['gc_maxlifetime'] = self::EXTERNAL_GC_MAXLIFETIME_IN_SECONDS;
    }
    else {
      $options['gc_maxlifetime'] = self::INTERNAL_GC_MAXLIFETIME_IN_SECONDS;
    }

    return $options;
  }

}
