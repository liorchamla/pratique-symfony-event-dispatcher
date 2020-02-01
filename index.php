<?php

/**
 * QUATRIEME PARTIE : LES PRIORITES DES LISTENERS
 * ------------
 * 
 * On a réglé beaucoup de problèmes ... MAIS.
 * 
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
 * 
 * DECOUVRONS LES PRIORITES :
 * -------------
 * Lorsqu'on ajoute un listener au dispatcher sur un événement donné, on peut aussi spécifier une priorité afin que le dispatcher en tienne
 * compte lorsque l'événement apparaitra.
 * 
 * Le nombre le plus haut (le plus important donc) a la priorité sur le nombre le plus bas. Par défaut, la priorité assigné à chaque
 * listener est de 0.
 * 
 * On va donc donner des priorités à nos Listeners pour qu'ils soient appelés dans l'ordre que l'on veut, du plus grand au plus petit.
 * 
 * Et voilà, le soucis est réglé !
 * 
 * --------------
 * Dans la prochaine section, on découvrira la notion de subscribers : vous l'avez remarqué, ici, nous avons des objets qui parfois possèdent
 * plusieurs méthodes qui vont être attachées au dispatcher. On peut encore refactoriser ces codes en créant des classes de type Subscriber.
 * 
 * Elles permettent de rationnaliser la configuration des listeners en un seul endroit : dans la classe elle-même.
 * 
 * ----------------
 * Pour bien comprendre cette section, regardez en particulier les fichiers suivantes :
 * - index.php : on donne des priorités à nos listeners
 */

use App\Controller\OrderController;
use App\Database;
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
// Notez que nous donnons un dernier paramètre numérique à la fonction addListener() : c'est la priorité.
// Rappelez vous que la priorité ira du nombre le plus grand au nombre le plus petit et se calcule PAR EVENEMENT
// On a donc ici deux listeners sur l'événement order.before_save (le 2 et le 1) puis deux listeners sur l'événement order.after_save (le 2 et
// le 1)

// 1. L'envoi de mail au stock quand une commande est en cours de création (juste avant insertion en base de données)
$dispatcher->addListener('order.before_save', [$orderMailingListener, 'onBeforeOrderIsCreated'], 1);
// 2. Le log de la commande en cours de création (juste avant insertion en base de données)
$dispatcher->addListener('order.before_save', [$orderLoggerListener, 'onBeforeOrderIsCreated'], 2);
// 3. L'envoi de SMS au client après enregistrement de la commande (juste après insertion en base)
$dispatcher->addListener('order.after_save', [$orderSmsListener, 'onAfterOrderIsCreated'], 1);
// 4. L'envoi de mail au client après enregistrement de la commande (juste après insertion en base)
$dispatcher->addListener('order.after_save', [$orderMailingListener, 'onAfterOrderIsCreated'], 2);

/**
 * ON PEUT CONNAITRE LES PRIORITES SI ON LE SOUHAITE :
 * -----------
 * Imaginons que vous ayez une dizaine ou une vingtaine de listeners déclarés dans des fichiers différents à divers endroits, ça commencerait
 * à être complexe de savoir qui a quelle priorité et où placer un nouveau listener dans la hiérarchie des priorités.
 * 
 * Pas de panique ! 
 * 
 */

// On récupère les listeners sur un événement donné
$listeners = $dispatcher->getListeners('order.before_save');

var_dump("Priorité des listeners sur l'évenement order.before_save :");

// Pour chaque listener, on demande quelle est sa priorité sur l'événement donné
foreach ($listeners as $listener) {
    $priority = $dispatcher->getListenerPriority('order.before_save', $listener);
    var_dump([
        'priority' => $priority,
        'listener' => $listener
    ]);
}


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
