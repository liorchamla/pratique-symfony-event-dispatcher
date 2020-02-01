<?php

use App\Database;
use App\Logger;
use App\Mailer\Mailer;
use App\Subscriber\OrderLoggerSubscriber;
use App\Subscriber\OrderMailingSubscriber;
use App\Subscriber\OrderSmsSubscriber;
use App\Texter\SmsTexter;
use Symfony\Component\EventDispatcher\EventDispatcher;

require __DIR__ . '/../vendor/autoload.php';

// Instanciation des services
$database = new Database(); // Une connexion fictive à la base de données (en vrai ça ne fait que des var_dump)
$mailer = new Mailer(); // Un service fictif d'envoi d'emails (là aussi, que du var_dump)
$smsTexter = new SmsTexter(); // Un service fictif d'envoi de SMS (là aussi que du var_dump)
$logger = new Logger(); // Un service de log (qui ne fait que du var_dump aussi)
$dispatcher = new EventDispatcher();

// Nos subscribers :
$orderMailingSubscriber = new OrderMailingSubscriber($mailer, $logger);
$orderSmsSubscriber = new OrderSmsSubscriber($smsTexter, $logger);
$orderLoggerSubscriber = new OrderLoggerSubscriber($logger);

// On attache au dispatcher :
$dispatcher->addSubscriber($orderMailingSubscriber);
$dispatcher->addSubscriber($orderSmsSubscriber);
$dispatcher->addSubscriber($orderLoggerSubscriber);
