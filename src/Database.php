<?php

namespace App;

use App\Model\Order;

class Database
{
    public function insertOrder(Order $order)
    {
        var_dump("------------", "DEBUT D'INSERTION FICTIVE EN BDD : ", $order, "FIN D'INSERTION FICTIVE");
    }
}
