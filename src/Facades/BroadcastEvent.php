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
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'broadcast.events';
    }
}
