<?php

/* * ***************************************************************************************************** */
/** poker playing defaults
 * Default amount given to every practice player and temporarily every player until purses are managed.
 */
$defaultTableMin = 10000;
/**
 * The default number of seats in a casino table until table management is implemented.
 */
$numberSeats = 4;

$buyInMultiplier = 30;

/* * ***************************************************************************************************** */
$dateTimeFormat = 'Y-m-d H:i:s';

$defaultAvatarUrl = 'Avatar_user0.jpeg';
/* * ***************************************************************************************************** */
// time outs
$playerTimeOut = 'PT2M'; // period after which a player is considered inactive
$instanceTimeOut = 'PT1M'; // period after which an instance is considered inactive
$playExpiration = 'PT20S'; // time given to a player to make a move
$practiceExpiration = 'PT2S'; // time between practice player moves
$moveTimeOut = 'PT20M'; // time when next move is purged from the queue.
$sessionExpiration = 'PT24H';
/* * ***************************************************************************************************** */
/* messaging - centralized config parmeters helps with deployment */
$rabbitmq_default_port = 61613;
$rabbitmq_default_host = 'localhost';
$rabbitmq_default_exchange = 'direct';
$rabbitmq_default_vhost = '/';
?>
