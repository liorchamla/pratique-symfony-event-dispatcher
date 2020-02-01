<?php

/**
 * CINQUIEME PARTIE : ADIEU LES LISTENERS, BIENVENUE AUX SUBSCRIBERS
 * ------------
 * 
 * Vous l'avez remarqué, ici, nous avons des objets qui parfois possèdent plusieurs méthodes qui vont être attachées au dispatcher. 
 * On peut encore refactoriser ces codes en créant des classes de type Subscriber.
 * 
 * DIFFERENCE ENTRE LISTENER ET SUBSCRIBER :
 * -----------
 * Un listener est un CALLABLE, un code que l'on peut appeler (CALL), c'est donc au choix :
 * - une fonction annonyme (closure)
 * - une fonction définie
 * - une méthode d'un objet
 * 
 * C'est très flexible, mais on peut trouver que cela manque un peu d'organisation.
 * 
 * Un subscriber est une classe dont le but est clairement de répondre à des événements du dispatcher. Elle implémente une interface
 * EventSubscriberInterface et a obligation de déclarer elle-même à quels événements elle veut répondre et avec laquelle de ses méthodes !
 * 
 * LES AVANTAGES DU SUBSCRIBER :
 * -----------
 * Le subscriber est donc une classe qui possède les méthodes qu'elle veut, mais surtout elle possède une méthode getSubscribedEvents où elle
 * définit quels sont les événements qui l'intéresse, quelles méthodes devront être appélées et quelles seront les priorités.
 * 
 * Donc quand on enregistre un subscriber auprès du dispatcher, on n'a rien d'autre à préciser, le subscriber le fait lui-même !
 * 
 * On centralise donc la configuration dans la classe elle-même, et on allège les déclarations faites auprès du dispatcher !
 * 
 * LES INCONVENIENTS DU SUBSCRIBER :
 * -----------
 * Là où avec les Listeners on pouvait constater en un point donné de quels listeners étaient attachés à quels événement, on doit désormais
 * aller ouvrir chaque subscriber pour voir qui est attaché à quel événement. On a décentraliser la configuration, ce qui parfois peut être
 * embêttant !
 * 
 * ----------------
 * Pour bien comprendre cette section, regardez en particulier les fichiers suivantes :
 * - index.php : on créé nos subscribers et on les attache au dispatcher
 * - src/Subscriber : on transforme les listeners en un subscribers
 */

use App\Controller\OrderController;
use App\Database;
use App\Subscriber\OrderMailingSubscriber;
use App\Subscriber\OrderSmsSubscriber;
use App\Logger;
use App\Mailer\Mailer;
use App\Subscriber\OrderLoggerSubscriber;
use App\Texter\SmsTexter;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
$dispatcher = new EventDispatcher();

// Nos subscribers :
$orderMailingSubscriber = new OrderMailingSubscriber($mailer, $logger);
$orderSmsSubscriber = new OrderSmsSubscriber($smsTexter, $logger);
$orderLoggerSubscriber = new OrderLoggerSubscriber($logger);
// Remplace l'ancien code :
// Nos listeners (voir le dossier src/Listener) :
// $orderMailingListener = new OrderMailingListener($mailer, $logger);
// $orderSmsListener = new OrderSmsListener($smsTexter, $logger);
// $orderLoggerListener = new OrderLoggerListener($logger);

$dispatcher->addSubscriber($orderMailingSubscriber);
$dispatcher->addSubscriber($orderSmsSubscriber);
$dispatcher->addSubscriber($orderLoggerSubscriber);

// Remplace l'ancien code :
// Mise en place des écoutes :
// Notez que nous donnons un dernier paramètre numérique à la fonction addListener() : c'est la priorité.
// Rappelez vous que la priorité ira du nombre le plus grand au nombre le plus petit et se calcule PAR EVENEMENT
// On a donc ici deux listeners sur l'événement order.before_save (le 2 et le 1) puis deux listeners sur l'événement order.after_save (le 2 et
// le 1)
// 1. L'envoi de mail au stock quand une commande est en cours de création (juste avant insertion en base de données)
// $dispatcher->addListener('order.before_save', [$orderMailingListener, 'onBeforeOrderIsCreated'], 1);
// 2. Le log de la commande en cours de création (juste avant insertion en base de données)
// $dispatcher->addListener('order.before_save', [$orderLoggerListener, 'onBeforeOrderIsCreated'], 2);
// 3. L'envoi de SMS au client après enregistrement de la commande (juste après insertion en base)
// $dispatcher->addListener('order.after_save', [$orderSmsListener, 'onAfterOrderIsCreated'], 1);
// 4. L'envoi de mail au client après enregistrement de la commande (juste après insertion en base)
// $dispatcher->addListener('order.after_save', [$orderMailingListener, 'onAfterOrderIsCreated'], 2);


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
