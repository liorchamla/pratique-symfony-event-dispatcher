<?php

namespace App\Listener;

use App\Event\OrderEvent;
use App\Logger;
use App\Mailer\Email;
use App\Mailer\Mailer;

/**
 * A LA DECOUVERTE D'UN LISTENER :
 * ----------
 * Vous l'avez vu dans le fichier index.php : un listener peut prendre 3 formes :
 * 1) Une closure (fonction annonyme)
 * 2) Une fonction définie
 * 3) La méthode d'un objet instancié
 * 
 * Ici on créé une classe pour porter la méthode onBeforeOrderIsCreated et c'est cette méthode que l'on veut attacher au dispatcher pour
 * l'événément "order.before_save" :)
 * 
 */
class OrderMailingListener
{
    protected $mailer;
    protected $logger;

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
