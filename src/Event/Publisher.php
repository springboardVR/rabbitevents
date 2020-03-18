<?php

namespace Nuwber\Events\Event;

use ReflectionClass;
use Illuminate\Support\Arr;
use Interop\Amqp\AmqpTopic;
use Interop\Queue\Exception;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpProducer;
use Illuminate\Support\InteractsWithTime;
use Nuwber\Events\Support\Testing\PublisherFake;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;

class Publisher
{
    use InteractsWithTime;

    /**
     * @var AmqpTopic
     */
    private $topic;

    /**
     * @var AmqpContext
     */
    private $context;

    public function __construct(AmqpContext $context, AmqpTopic $topic)
    {
        $this->context = $context;
        $this->topic = $topic;
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
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $message = $this->context->createMessage($payload);
        $message->setRoutingKey($event);

        return $this->sendMessage($message);
    }

    public function sendMessage(AmqpMessage $message, int $delay = 0)
    {
        /** @var AmqpProducer $producer */
        $producer = $this->context->createProducer();

        try {
            $producer->setDeliveryDelay($this->secondsUntil($delay) * 1000);
        } catch (DeliveryDelayNotSupportedException $e) {
        }

        $producer->send($this->topic, $message);

        return $this;
    }

    public function publish($event, array $payload = [])
    {
        return $this->send(...$this->extractEventAndPayload($event, $payload));
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
