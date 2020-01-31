<?php

/**
 * PREMIERE PARTIE : BIENVENUE DANS CE COURS SUR LE COMPOSANT SYMFONY/EVENT-DISPATCHER !
 * -------------
 * Dans ce cours, nous allons découvrir un composant que doit nous permettre de rendre notre code :
 * 1) plus propre
 * 2) plus stable
 * 3) plus évolutif 
 * 
 * DESCRIPTION DE L'APPLICATION ACTUELLE :
 * -------------
 * Pour l'instant notre code est plutôt simple, le but est de présenter un formulaire de commande de produits au visiteur puis
 * d'enregistrer la commande passée.
 * 
 * Tout se passe dans le OrderController qui possède deux méthodes :
 * - displayOrderForm() qui affiche simplement le formulaire
 * - handleOrder() qui s'occupe d'enregistrer la commande lorsque le formulaire est soumis
 * 
 * Très simple. Pourtant, en voyant la classe OrderController vous vous rendrez compte que beaucoup de choses se passent :
 * - Envoi d'emails,
 * - Envoi de SMS
 * - Enregistrement en base de données de la commande
 * - Ecriture de journaux (logs)
 * 
 * Bref, cette classe fait beaucoup trop de choses et le pire, c'est qu'à la demande de notre client, elle pourrait encore grandir et grandir.
 * 
 * VIOLATION DU PRINCIPE DE RESPONSABILITE UNIQUE :
 * ------------
 * Une bonne pratique de la POO, c'est le SRP (Single Responsibility Principle) qui postule qu'une classe ne devrait avoir qu'une seule mission
 * particulière. Lorsqu'on regarde une classe et qu'on pose la question : Ai-je plus d'une seule raison de modifier cette classe à l'avenir ?
 * 
 * Si on répond "Oui", alors on sait que la classe ne respecte pas le principe de responsabilité unique. Ici, nous avons de multiples raisons
 * de modifier la classe OrderController dans le futur, en voici quelques unes :
 * - Si je veux modifier le sujet ou le corps l'email qui sera envoyé en interne (au stock)
 * - Si je veux modifier le sujet ou le corps de l'email qui sera envoyé au client
 * - Si je veux modifier le SMS envoyé au client
 * - Si je veux ajouter un nouveau comportement ou supprimer un comportement particulier
 * 
 * En bref, le controller fait bien plus que simplement enregistrer une commande, il a BEAUCOUP DE RESPONSABILITES, pas une seule.
 * 
 * VIOLATION DU PRINCIPE OPEN/CLOSED :
 * --------------
 * Une autre bonne pratique de la POO, c'est le OCP (Open Closed Principle) qui postule qu'une classe ne devrait plus être modifiée une fois
 * qu'elle a été codée et livrée.
 * 
 * Or comme dit un peu plus haut, j'ai beaucoup de raisons de revenir sur cette classe, que ce soit pour modifier tel ou tel envoi de mail ou
 * de SMS mais aussi si je veux ajouter ou supprimer un comportement lors de la prise de commande.
 * 
 * LE COMPOSANT EVENT DISPATCHER DE SYMFONY :
 * --------------
 * Après avoir fait un peu le tour de ce code et l'avoir bien compris, vous pourrez accéder à la section suivante pour voir comment le 
 * composant symfony/event-dispatcher (composer require symfony/event-dispatcher) va nous aider dans notre recherche de la pureté de la POO :D
 */

use App\Controller\OrderController;
use App\Database;
use App\Logger;
use App\Mailer\Mailer;
use App\Texter\SmsTexter;

require __DIR__ . '/vendor/autoload.php';

/**
 * INSTANCIATION DES OBJETS DE BASE :
 * -----------
 * Nous instancions les objets basiques nécessaires à l'application
 */
$database = new Database(); // Une connexion fictive à la base de données (en vrai ça ne fait que des var_dump)
$mailer = new Mailer(); // Un service fictif d'envoi d'emails (là aussi, que du var_dump)
$smsTexter = new SmsTexter(); // Un service fictif d'envoi de SMS (là aussi que du var_dump)
$logger = new Logger(); // Un service de log (qui ne fait que du var_dump aussi)

// Notre controller qui a besoin de tout ces services
$controller = new OrderController($database, $mailer, $smsTexter, $logger);

// Si le formulaire a été soumis
if (!empty($_POST)) {
    // On demande au controller de gérer la commande
    $controller->handleOrder();
    // Et on arrête là.
    return;
}

// Sinon, on affiche simplement le formulaire
$controller->displayOrderForm();
