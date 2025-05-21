<?php

namespace Drupal\myportal_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the LocationAutocompleteController class.
 *
 * @package Drupal\myportal_weather\Controller
 */
class LocationAutocompleteController extends ControllerBase {

  /**
   * The geocoding service.
   *
   * @var \Drupal\myportal_weather\Service\Geocoding\GeocodingProviderInterface
   */
  protected $geocodingService;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $geocoding_service = $container->get('myportal_weather.geocoding.openweathermap');
    assert($geocoding_service instanceof GeocodingProviderInterface);
    $instance->geocodingService = $geocoding_service;

    return $instance;
  }

  /**
   * Retrieve matches.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Contains an array of matches.
   */
  public function autocomplete(Request $request) {
    $matches = [];

    $part = $request->query->get('q');
    if ($part) {
      // Escape user input.
      $part = preg_quote($part);

      $locations = $part ? $this->geocodingService->getCoordinatesByLocationName($part) : [];
      foreach ($locations as $location) {
        $matches[] = $location->getName();
      }
    }

    return new JsonResponse($matches);
  }

}
