<?php

namespace App\Listener;

use App\Event\OrderEvent;
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

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function onBeforeOrderIsCreated(OrderEvent $orderEvent)
    {
        var_dump("ORDERMAILINGLISTENER EST APPELE ! HOURRA !", $orderEvent);
    }
}
