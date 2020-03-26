<?php

namespace Nuwber\Events\Support\Testing;

use ReflectionClass;
use Illuminate\Support\Arr;
use Interop\Queue\Exception;
use Nuwber\Events\Event\Publisher;
use Nuwber\Events\Event\ShouldPublish;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Support\InteractsWithTime;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\InvalidDestinationException;

class PublisherFake
{
    use InteractsWithTime;

    protected array $events = [];

    public static function setup()
    {
        app()->singleton(Publisher::class, PublisherFake::class);

        return app(Publisher::class);
    }

    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $eventsToFake
     * @return \Illuminate\Support\Testing\Fakes\EventFake
     */
    public static function teardown($eventsToFake = [])
    {
        app()->singleton(Publisher::class);
    }

    /**
     * Publishes payload
     *
     * @param  string  $event
     * @param  array  $payload
     * @return Publisher
     *
     * @throws Exception
     * @throws InvalidDestinationException
     * @throws InvalidMessageException
     */
    public function send(string $event, array $payload, int $delay = 0): self
    {
        $this->events[$event][] = json_decode(json_encode($payload));

        return $this;
    }

    public function publish($event, array $payload = [])
    {
        return $this->send(...$this->extractEventAndPayload($event, $payload));
    }

    /**
     * Assert if an event was published based on a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertPublished($event, $callback = null)
    {
        if (is_int($callback)) {
            return $this->assertPublishedTimes($event, $callback);
        }

        PHPUnit::assertTrue(
            $this->published($event, $callback)->count() > 0,
            "The expected [{$event}] message was not published."
        );
    }

    /**
     * Assert if a event was published a number of times.
     *
     * @param  string  $event
     * @param  int  $times
     * @return void
     */
    public function assertPublishedTimes($event, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->published($event)->count()) === $times,
            "The expected [{$event}] message was published {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if an event was published based on a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotPublished($event, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->published($event, $callback)->count() === 0,
            "The unexpected [{$event}] message was published."
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function published($event, $callback = null)
    {
        if (class_exists($event,false)) {
            $event = app($event)->publishEventKey();
        }

        if (! $this->hasPublished($event)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->events[$event])->filter(function ($arguments) use ($callback) {
            if (! is_array($arguments)) {
                $arguments = [$arguments];
            }
            return $callback(...$arguments);
        });
    }

    /**
     * Determine if the given event has been published.
     *
     * @param  string  $event
     * @return bool
     */
    public function hasPublished($event)
    {
        return isset($this->events[$event]) && ! empty($this->events[$event]);
    }

    /**
     *  Extract event and payload and prepare them for publishing.
     *
     * @param $event
     * @param  array  $payload
     * @return array
     */
    private function extractEventAndPayload($event, array $payload)
    {
        if (is_object($event) && $this->eventShouldBePublished($event)) {
            return [$event->publishEventKey(), $event->toPublish()];
        }

        if (is_string($event)) {
            return [$event, Arr::wrap($payload)];
        }

        throw new \InvalidArgumentException('Event must be a string or implement `ShouldPublish` interface');
    }

    /**
     * Determine if the event handler class should be queued.
     *
     * @param  object  $event
     * @return bool
     * @throws \ReflectionException
     */
    protected function eventShouldBePublished($event)
    {
        try {
            return (new ReflectionClass(get_class($event)))
                ->implementsInterface(ShouldPublish::class);
        } catch (Exception $e) {
            return false;
        }
    }
}
