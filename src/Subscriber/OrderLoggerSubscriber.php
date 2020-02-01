<?php

namespace App\Subscriber;

use App\Event\OrderEvent;
use App\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderLoggerSubscriber implements EventSubscriberInterface
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            'order.before_save' => ['onBeforeOrderIsCreated', 2]
        ];
    }

    public function onBeforeOrderIsCreated(OrderEvent $orderEvent)
    {
        $order = $orderEvent->getOrder();

        $this->logger->log("Commande en cours pour {$order->getQuantity()} {$order->getProduct()}");
    }
}
