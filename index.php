<?php

/**
 * DEUXIEME PARTIE : INSTALLATION ET PREMIERE UTILISATIN DE L'EVENT DISPATCHER DE SYMFONY
 * ------------
 * Dans cette section, on ne va pas changer grand chose à notre OrderController, on va simplement mettre en place les prémices de notre
 * gestion d'événements.
 * 
 * Nous avons vu dans la première partie que notre code ne respectait pas au moins deux principes SOLID (bonnes pratiques de POO) et nous
 * cherchons à remédier à cette situation :
 * 1) Le principe de responsabilité unique : la classe OrderController avait beaucoup trop de responsabilités (enregistrement, envoi de mails,
 * envoi de SMS, logs, etc)
 * 2) Le principe open/closed : chaque modification de choses annexes (emails, SMS, logs) et chaque nouveauté ou choses qu'on voudrait enlever
 * amène une modification de la classe OrderController. Elle n'est donc pas stable et peut devenir à terme impossible à maintenir et source
 * d'erreurs récurrentes !
 * 
 * A L'ASSAUT DU DESIGN PATTERN : EVENT DISPATCHER
 * ------------
 * Il existe un patron de conception (un patron de conception (design pattern) est une organisation de code pensée pour répondre à un problème
 * donné) qui s'appelle l'Event Dispatcher. L'idée est simple :
 * 1) Vous avez un objet qu'on appelle l'Event Dispatcher (le distributeur d'événements)
 * 2) On peut appeler le dispatcher quand on le souhaite pour le prévenir que quelque chose vient de se produire (notion d'événement)
 * 3) Le dispatcher exécute toutes les fonctions qu'on lui a demandé d'exécuter pour un événément donné.
 * 
 * Donc en gros, avec ce pattern, depuis notre controller on peut prévenir le dispatcher par exemple que :
 * - NOUS ALLONS BIENTÔT ENREGISTRER LA COMMANDE
 * - NOUS VENONS D'ENREGISTRER LA COMMANDE
 * 
 * Et c'est au dispatcher d'exécuter tel ou tel code quand chacun de ces événements à lieu. Par exemple :
 * - "NOUS ALLONS ENREGISTRER LA COMMANDE" => Le dispatcher sait qu'à ce moment, il faut envoyer un email au stock !
 * - "NOUS AVONS ENREGISTRE LA COMMANDE" => Le dispatcher sait qu'à ce moment, il doit :
 * ---> Envoyer un mail au client
 * ---> Envoyer un SMS au client
 * 
 * Donc notre OrderController n'aurait vraiment plus grand chose à faire qu'à réellement enregistrer la commande et prévenir le dispatcher de
 * ce qu'il va faire ou de ce qu'il vient de faire.
 * 
 * LA NOTION D'EVENEMENT :
 * ------------
 * Vous l'aurez compris, ce que l'on fait avec le dispatcher finalement, c'est gérer des EVENEMENTS. Quand un code veut que le dispatcher
 * prenne en charge quelque chose, il lui passe un événement. Bien sur il faut qu'on puisse distinguer un événement d'un autre. C'est pour ça
 * qu'on donne un nom à chaque événement, comme par exemple :
 * - order.before_save : on s'apprête à sauvegarder une nouvelle commande
 * - order.after_save : on vient de sauvegarder une nouvelle commande
 * - order.cancelled : un commande vient d'être annulée
 * - order.paid : une commande vient d'être payée
 * 
 * On peut inventer tous les événements qu'on veut. On peut ensuite prévenir le dispatcher que tel ou tel événement vient d'avoir lieu et c'est
 * à lui de dire si il veut faire quelque chose ou pas.
 * 
 * LES DONNEES SPECIFIQUES D'UN EVENEMENT :
 * -----------
 * Quand le dispatcher exécute un code correspond à un événement, il est important qu'il dispose des données essentielles. Par exemple, si on
 * exécute un code qui répond à l'événement order.after_save, il est très probable qu'on ait besoin des informations de la commande.
 * 
 * On peut passer au dispatcher des données additionnelles. C'est L'OBJET QUI REPRESENTE L'EVENEMENT.
 * 
 * Attention : je n'ai pas dit qu'on devait passer l'objet Order lui-même par exemple, non, c'est un objet qui représente l'événement qui se 
 * produit. Peut-être d'ailleurs contient il l'objet Order, mais pas que !
 * 
 * Ces objets doivent hériter de la classe Event et la classe que l'on va créer pour représenter un événement fini souvent par Event.
 * 
 * Nous allons créer la classe OrderEvent pour représenter un événement sur une commande. Cette classe contiendra l'Order en question
 * et sera passée au dispatcher lors de tout événement concernant une commande !
 * 
 * ----------------
 * Pour bien comprendre cette section, regardez en particulier les fichiers suivantes :
 * - composer.json : on ajoute la dépendance à symfony/event-dispatcher
 * - src/Event/OrderEvent.php : on créé la classe qui représente les événements concernant les Order (commandes)
 * - src/Listener/OrderMailingListener : on créé notre premier listener
 * - index.php : on créé le $dispatcher, on lui attache les listeners et on le passe au controller
 */

use App\Controller\OrderController;
use App\Database;
use App\Event\OrderEvent;
use App\Listener\OrderMailingListener;
use App\Logger;
use App\Mailer\Mailer;
use App\Texter\SmsTexter;
use Symfony\Component\EventDispatcher\EventDispatcher;

require __DIR__ . '/vendor/autoload.php';


/**
 * DECOUVRONS LE DISPATCHER :
 * -------
 * C'est lui qui est chargé de "dispatcher", distribuer, les données nécessaires à des fonctions ou des objets qui doivent ensuite gérer ces
 * données.
 */
$dispatcher = new EventDispatcher();

/**
 * INSTANCIATION DES OBJETS DE BASE :
 * -----------
 * Nous instancions les objets basiques nécessaires à l'application
 */
$database = new Database(); // Une connexion fictive à la base de données (en vrai ça ne fait que des var_dump)
$mailer = new Mailer(); // Un service fictif d'envoi d'emails (là aussi, que du var_dump)
$smsTexter = new SmsTexter(); // Un service fictif d'envoi de SMS (là aussi que du var_dump)
$logger = new Logger(); // Un service de log (qui ne fait que du var_dump aussi)

/**
 * DECOUVRONS LES LISTENERS :
 * ---------
 * Les listeners sont des fonctions ou des objets qui sont appelés par le dispatcher lorsqu'un événement particulier se produit. On dit qu'ils
 * écoutent (LISTEN) le dispatcher, toujours à l'affut et toujours prêts à travailler si un événement qui les intéressent se produit !
 * 
 * Ici, nous avons créer une classe OrderMailingListener, qui possédera un service de mailing.
 * 
 * Un listener, c'est juste un code qu'on veut exécuter (on appelle cela un "callable", un code qu'on peut "call" (appeler)), cela peut-être :
 * 1) une fonction annonyme (une closure)
 * 2) une fonction définie
 * 3) la méthode d'un objet instancié
 */

/**
 * PREMIER EXEMPLE DE LISTENER (FONCTION ANNONYME)
 * ---------
 * Ici, on dit simplement au dispatcher : quand l'événement 'order.before_save' apparait, appelle la fonction que je te précise
 */
$dispatcher->addListener('order.before_save', function (OrderEvent $event) {
    var_dump("HOURRA, JE SUIS UN LISTENER EN CLOSURE !");
});

/**
 * DEUXIEME EXEMPLE DE LISTENER (FONCTION DEFINIE)
 * ---------
 * Ici, on définit tout d'abord un fonction (cela peut se faire dans un autre fichier que l'on "include" avant) et on dit au $dispatcher
 * qu'on souhaite que la fonction soit appelée sur un événement particulier
 */
function listenerExemple(OrderEvent $event)
{
    var_dump("HOURRA, JE SUIS UN LISTENER EN FONCTION DEFINIE !");
}
// Ici on dit simplement : quand cet événement arrive, appelle la fonction qui s'appelle "listernerExemple"
$dispatcher->addListener('order.before_save', 'listenerExemple');

/**
 * TROISIEME EXEMPLE DE LISTENER (METHODE D'OBJET)
 * ----------
 * Ici, on créé une instance d'un objet qui nous intéresse et nous expliquons au dispatcher :
 * "Quand un événement appelé "order.before_save" apparait, fais appel à la méthode 'onBeforeOrderIsCreated' de l'objet $orderMailingListener"
 */
$orderMailingListener = new OrderMailingListener($mailer);
$dispatcher->addListener('order.before_save', [$orderMailingListener, 'onBeforeOrderIsCreated']);
/**
 * CA Y EST ! Le dispatcher est au courant : "Dès qu'on me dit que l'événement 'order.before_save' arrive, j'appelle la méthode qu'on m'a
 * spécifié en lui passant les données de l'événement : c'est à elle de travailler désormais".
 * 
 * Donc : quand le OrderController va "émettre" l'événement "order.before_save", il faut s'attendre à ce que :
 * 1) La fonction annonyme qu'on a attachée soit appelée
 * 2) La fonction définie qu'on a attachée soit appelée
 * 3) La méthode 'onBeforeOrderIsCreated' de l'objet $orderMailingListener soit appelée
 * 
 * Pour l'instant elle ne font que de simple var_dump, mais elles pourraient faire bien plus !
 *
 * POURQUOI NOS FONCTIONS / METHODES RECOIVENT UN PARAMETRE DE TYPE OrderEvent ?!?
 * ----------
 * Si vous regardez le fichier src/Controller/OrderController, vous allez vous apercevoir qu'il y a une ligne :
 * $this->dispatcher->dispatch(new OrderEvent($order), 'order.before_save');
 * 
 * Vous avez bien compris, le controller appelle le dispatcher et lui dit "Hey, passe cet objet OrderEvent à tous ceux qui s'intéressent
 * à l'événement 'order.before_save'. Et c'est bien ce que va faire le dispatcher, il va passer à la fonction à appeler l'objet OrderEvent
 * qu'on lui précise !
 * 
 * Le but est que la fonction puisse travailler avec :)
 */

// Notre controller qui a besoin de tout ces services
// Notez que désormais, on lui passe le $dispatcher pour qu'il puisse y faire appel si besoin :)
$controller = new OrderController($database, $mailer, $smsTexter, $logger, $dispatcher);

// Si le formulaire a été soumis
if (!empty($_POST)) {
    // On demande au controller de gérer la commande
    $controller->handleOrder();
    // Et on arrête là.
    return;
}

// Sinon, on affiche simplement le formulaire
$controller->displayOrderForm();
