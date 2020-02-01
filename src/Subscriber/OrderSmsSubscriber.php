<?php

namespace App\Subscriber;

use App\Event\OrderEvent;
use App\Logger;
use App\Texter\Sms;
use App\Texter\SmsTexter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSmsSubscriber implements EventSubscriberInterface
{
    protected $texter;
    protected $logger;

    public static function getSubscribedEvents()
    {
        return [
            'order.after_save' => ['onAfterOrderIsCreated', 1]
        ];
    }

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
