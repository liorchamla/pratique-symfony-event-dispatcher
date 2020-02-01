<?php

/**
 * DERNIERE PARTIE : REFACTORING FINAL
 * ------------
 * On a juste réorganisé notre code afin que l'index soit plus léger et centraliser les services et le dispatcher dans le fichier
 * config/bootstrap.php
 */

use App\Controller\OrderController;

require __DIR__ . '/config/bootstrap.php';

$controller = new OrderController($database, $dispatcher);

// Si le formulaire a été soumis
if (!empty($_POST)) {
    // On demande au controller de gérer la commande
    $controller->handleOrder();
    // Et on arrête là.
    return;
}

// Sinon, on affiche simplement le formulaire
$controller->displayOrderForm();
