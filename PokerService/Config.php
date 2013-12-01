<?php

$dbName = 'cazito5_sprint8';
/* * ***************************************************************************************************** */
/** poker playing defaults */
$defaultTableMin = 10000; // practice
$numberSeats = 4; // max number of seats
$buyInMultiplier = 30;

/* * ***************************************************************************************************** */
// formats
$dateTimeFormat = 'Y-m-d H:i:s';
date_default_timezone_set("America/Los_Angeles");

/* * ***************************************************************************************************** */
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
// cheating time outs
$cHeartMarkerTimeOut = 'PT5M';
$cClubMarkerTimeOut = 'PT1M';
$cDiamondMarkerTimeOut = 'PT3M';
$cRiverShufflerTimeOut = 'PT10M'; // the Swap Time Out is the same
$cPokerPeekerTimeOut = 'PT15M';
$cSocialSpotterTimeOut = 'PT60M';
$cSocialSpotterDuration = 'PT45M';
$cSnakeOilMarkerTimeOut = 'PT30M';
$cAntiOilMarkerTimeOut = 'PT60M';
//$cRiverBendoTimeOut = 'PT10M';

/* * ***************************************************************************************************** */
/* messaging - centralized config parmeters helps with deployment */
$rabbitmq_default_port = 61613;
$rabbitmq_default_host = 'localhost';
$rabbitmq_default_exchange = 'direct';
$rabbitmq_default_vhost = '/';
?>
