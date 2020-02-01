<?php

namespace App\Listener;

use App\Event\OrderEvent;
use App\Logger;
use App\Texter\Sms;
use App\Texter\SmsTexter;

class OrderSmsListener
{
    protected $texter;
    protected $logger;

    public function __construct(SmsTexter $texter, Logger $logger)
    {
        $this->texter = $texter;
        $this->logger = $logger;
    }

    public function onAfterOrderIsCreated(OrderEvent $orderEvent)
    {
        $order = $orderEvent->getOrder();

        $sms = new Sms();
        $sms->setNumber($order->getPhoneNumber())
            ->setText("Merci pour votre commande de {$order->getQuantity()} {$order->getProduct()} !");

        $this->texter->send($sms);

        $this->logger->log("SMS de confirmation envoyé à {$order->getPhoneNumber()} !");
    }
}
