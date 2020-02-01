<?php

namespace App\Event;

use App\Model\Order;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * REPRESENTER UN EVENEMTN SOUS LA FORME D'UNE CLASSE :
 * ------------
 * Cette classe n'a aucun autre but que de représenter un événement. Elle porte des données, et elle sera passée à chaque fonction qui
 * s'intéresse à un événement "order.before_save" ou "order.after_save" :-)
 */
class OrderEvent extends Event
{
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the value of order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the value of order
     *
     * @return  self
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }
}
