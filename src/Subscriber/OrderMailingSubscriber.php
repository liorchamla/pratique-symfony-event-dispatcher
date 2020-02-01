<?php

namespace App\Subscriber;

use App\Event\OrderEvent;
use App\Logger;
use App\Mailer\Email;
use App\Mailer\Mailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderMailingSubscriber implements EventSubscriberInterface
{
    protected $mailer;
    protected $logger;

    public static function getSubscribedEvents()
    {
        // Attention ici à ne pas confondre les priorités :
        // onBeforeOrderIsCreated a une priorité de 1, car dans le OrderLoggerSubscriber, on a une fonction avec priorité 2 qui passera avant
        // onAfterOrderIsCreated a une priorité de 2, car elle doit être appelée avant la méthode du OrderSmsSubscriber qui aura une priorité 
        // de 1 !
        // LES PRIORITES SONT PAR EVENEMENT !
        return [
            'order.before_save' => ['onBeforeOrderIsCreated', 1],
            'order.after_save' => ['onAfterOrderIsCreated', 2]
        ];
    }

    public function __construct(Mailer $mailer, Logger $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function onBeforeOrderIsCreated(OrderEvent $orderEvent)
    {
        $order = $orderEvent->getOrder();

        $email = new Email();
        $email->setSubject("Commande en cours")
            ->setBody("Merci de vérifier le stock pour le produit {$order->getProduct()} et la quantité {$order->getQuantity()} !")
            ->setTo("stock@maboutique.com")
            ->setFrom("web@maboutique.com");

        $this->mailer->send($email);
    }

    public function onAfterOrderIsCreated(OrderEvent $orderEvent)
    {
        $order = $orderEvent->getOrder();

        $email = new Email();
        $email->setSubject("Commande confirmée")
            ->setBody("Merci pour votre commande de {$order->getQuantity()} {$order->getProduct()} !")
            ->setFrom("web@maboutique.com")
            ->setTo($order->getEmail());

        $this->mailer->send($email);

        $this->logger->log("Email de confirmation envoyé à {$order->getEmail()} !");
    }
}
