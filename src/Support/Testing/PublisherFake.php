<?php

namespace Nuwber\Events\Support\Testing;

use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpContext;
use Nuwber\Events\Event\Publisher;

class PublisherFake extends Publisher
{
    /**
     * @var array
     */
    protected $eventsToFake;

    public function __construct(AmqpContext $context, AmqpTopic $topic, array $eventsToFake = [])
    {
        parent::__construct($context, $topic);
        $this->eventsToFake = $eventsToFake;
    }
}
