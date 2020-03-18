<?php

namespace Nuwber\Events\Facades;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Nuwber\Events\Support\Testing\RabbitEventFake;

/**
 * @see \Nuwber\Events\Dispatcher
 */
class BroadcastEvent extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $eventsToFake
     * @return \Illuminate\Support\Testing\Fakes\EventFake
     */
    public static function fake($eventsToFake = [])
    {
        static::swap($fake = new RabbitEventFake(static::getFacadeRoot(), $eventsToFake));
        return $fake;
    }

    /**
     * Replace the bound instance with a fake during the given callable's execution.
     *
     * @param  callable  $callable
     * @param  array  $eventsToFake
     * @return callable
     */
    public static function fakeFor(callable $callable, array $eventsToFake = [])
    {
        $originalDispatcher = static::getFacadeRoot();

        static::fake($eventsToFake);

        return tap($callable(), function () use ($originalDispatcher) {
            static::swap($originalDispatcher);
        });
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'broadcast.events';
    }
}
