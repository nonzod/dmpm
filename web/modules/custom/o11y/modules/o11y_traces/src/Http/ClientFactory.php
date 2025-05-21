<?php

namespace Drupal\o11y_traces\Http;

use Drupal\Core\Http\ClientFactory as CoreClientFactory;
use OpenTracing\Formats;

/**
 * Helper class to construct a HTTP client with Drupal specific config.
 */
class ClientFactory extends CoreClientFactory {

  /**
   * Constructs a new client object from some configuration.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  public function fromOptions(array $config = []) {
    $headers = [];

    /** @var \Drupal\o11y_traces\Opentracing $opentracing */
    $opentracing = \Drupal::service('o11y_traces.opentracing');
    $tracer = $opentracing->getTracer();
    $span = $opentracing->startSpan('external_call');

    try {
      $tracer->inject(
        $span->getContext(),
        Formats\TEXT_MAP,
        $headers
      );
    }
    catch (\Exception $e) {
    }

    return parent::fromOptions(['headers' => $headers]);
  }

}
