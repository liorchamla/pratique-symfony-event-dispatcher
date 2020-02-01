<?php

namespace App\Listener;

use App\Event\OrderEvent;
use App\Logger;

class OrderLoggerListener
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function onBeforeOrderIsCreated(OrderEvent $orderEvent)
    {
        $order = $orderEvent->getOrder();

        $this->logger->log("Commande en cours pour {$order->getQuantity()} {$order->getProduct()}");
    }
}
