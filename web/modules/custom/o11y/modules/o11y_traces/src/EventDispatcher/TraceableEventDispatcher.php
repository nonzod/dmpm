<?php

namespace Drupal\o11y_traces\EventDispatcher;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\o11y_traces\Opentracing;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class TraceableEventDispatcher.
 */
class TraceableEventDispatcher extends ContainerAwareEventDispatcher {

  /**
   * @var \Drupal\o11y_traces\Opentracing
   */
  private $tracing;

  public function setOpentracing(Opentracing $tracing) {
    $this->tracing = $tracing;
  }

  public function dispatch($event_name, Event $event = NULL) {
    if ($event === NULL) {
      $event = new Event();
    }

    if (isset($this->listeners[$event_name])) {
      // Sort listeners if necessary.
      if (isset($this->unsorted[$event_name])) {
        krsort($this->listeners[$event_name]);
        unset($this->unsorted[$event_name]);
      }

      // Invoke listeners and resolve callables if necessary.
      foreach ($this->listeners[$event_name] as $priority => &$definitions) {
        foreach ($definitions as $key => &$definition) {
          if (!isset($definition['callable'])) {
            $definition['callable'] = [
              $this->container->get($definition['service'][0]),
              $definition['service'][1],
            ];
          }
          if (is_array($definition['callable']) && isset($definition['callable'][0]) && $definition['callable'][0] instanceof \Closure) {
            $definition['callable'][0] = $definition['callable'][0]();
          }

          $span = $this->tracing->startSpan('Event: ' . $event_name);
          call_user_func($definition['callable'], $event, $event_name, $this);
          $span->finish();

          if ($event->isPropagationStopped()) {
            return $event;
          }
        }
      }
    }

    return $event;
  }

}
