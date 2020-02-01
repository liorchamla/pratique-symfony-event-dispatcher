<?php

/**
 * TROISIEME PARTIE : REFACTORING DU CODE DU CONTROLLER DANS DES LISTENERS
 * ------------
 * Rappelez vous de la première partie, nous avions vu que notre OrderController présentait deux problèmes majeurs :
 * 1) Il n'avait pas une responsabilité unique (enregistrer une commande) mais PLEIN de responsabilités (envoyer des emails, logger, envoyer
 * des SMS)
 * 2) Chaque modification de ces comportements annexes (emails, SMS, logs) et chaque ajout ou suppressin d'un de ces comportements 
 * demandait une modification du Controller lui-même, ce qui brisait le principe Open/Closed
 * 
 * C'est désormais terminé ! Grâce au design pattern EventDispatcher et au composant symfony/event-dispatcher qui l'implémente, on a réglé ce 
 * soucis :
 * 1) Le controller fait uniquement ce qu'il doit faire : enregistrer la commande. 
 * 2) Il prévient le dispatcher des étapes qu'il traverse
 * 3) Le dispatcher fait appel à des codes dédiés aux événements qu'on lui soumet
 * 4) Modifier une tâche se fait dans la classe appropriée :
 * - src/Listener/OrderLoggerListener.php si on veut modifier les logs
 * - src/Listener/OrderMailingListener.php si on veut modifier les emails
 * - src/Listener/OrderSmsListener.php si on veut modifier les SMS
 * 5) Le controller n'a plus besoin de recevoir dans son constructeur tous les services (texter, mailer, etc) qui ne l'intéressent pas
 * 
 * 
 * On a donc bien refactorisé notre code proprement afin que chose ait sa place et chaque place sa chose (paranoïa de l'informaticien).
 * 
 * Le code actuel fait exactement la même chose que le code de base, mais il est beaucoup plus évolutif et ordonné !
 * 
 * Tout est bien qui finit bien ! Vraiment ?
 * 
 * PROCHAINE SECTION : L'ORDRE D'APPEL DES LISTENERS
 * ---------------
 * Vous l'avez peut-être remarqué : en réalité notre code actuel ne fait pas exactement ce que faisait le code de base. Voilà ce qu'on 
 * faisait auparavant :
 * 1) Log de la commande en cours
 * 2) Email au stock pour le prévenir
 * 3) Email au client pour le remercier
 * 4) SMS au client pour le remercier
 * 
 * Or le code actuel, du fait de l'ordre dans lequel les listeners sont déclarés ne fait pas du tout la même chose :
 * 1) Email au stock
 * 2) Log de la commande
 * 3) SMS au client
 * 4) Email au client
 * 
 * Par défaut, les listeners sont appelés dans l'ordre dans lesquels on les a ajouté au dispatcher .. Est-il possible de gérer cet ordre ?
 * Nous le découvrirons dans la section suivante sur les priorités des listeners !
 * 
 * ----------------
 * Pour bien comprendre cette section, regardez en particulier les fichiers suivantes :
 * - src/Listener/OrderMailingListener.php : on met en place les e-mails envoyés avant et apres le passage d'une commande
 * - src/Listener/OrderLoggerListener.php : on met en place les logs à écrire pour le passage d'une commande
 * - src/Listener/OrderSmsListener.php : on met en place l'envoi de SMS au client après passage d'une commande
 * - index.php : on créé le $dispatcher, on lui attache les listeners et on le passe au controller
 */

use App\Controller\OrderController;
use App\Database;
use App\Event\OrderEvent;
use App\Listener\OrderLoggerListener;
use App\Listener\OrderMailingListener;
use App\Listener\OrderSmsListener;
use App\Logger;
use App\Mailer\Mailer;
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

// Nos listeners (voir le dossier src/Listener) :
$orderMailingListener = new OrderMailingListener($mailer, $logger);
$orderSmsListener = new OrderSmsListener($smsTexter, $logger);
$orderLoggerListener = new OrderLoggerListener($logger);

// Mise en place des écoutes :
// 1. L'envoi de mail au stock quand une commande est en cours de création (juste avant insertion en base de données)
$dispatcher->addListener('order.before_save', [$orderMailingListener, 'onBeforeOrderIsCreated']);
// 2. Le log de la commande en cours de création (juste avant insertion en base de données)
$dispatcher->addListener('order.before_save', [$orderLoggerListener, 'onBeforeOrderIsCreated']);
// 3. L'envoi de SMS au client après enregistrement de la commande (juste après insertion en base)
$dispatcher->addListener('order.after_save', [$orderSmsListener, 'onAfterOrderIsCreated']);
// 4. L'envoi de mail au client après enregistrement de la commande (juste après insertion en base)
$dispatcher->addListener('order.after_save', [$orderMailingListener, 'onAfterOrderIsCreated']);


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
