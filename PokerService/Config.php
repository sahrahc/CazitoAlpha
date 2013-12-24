<?php
// configure logging
/* * ***************************************************************************************************** */
$dbName = 'cazito5_sprint333';
/* * ***************************************************************************************************** */
/** poker playing defaults */
$defaultTableMin = 1000; // practice
$numberSeats = 4; // max number of seats
$buyInMultiplier = 300;

/* * ***************************************************************************************************** */
// formats
$dateTimeFormat = 'Y-m-d H:i:s';
date_default_timezone_set("America/Los_Angeles");

/* * ***************************************************************************************************** */
$defaultAvatarUrl = 'Avatar_user0.jpeg';

/* * ***************************************************************************************************** */
// time outs
//$playerTimeOut = 'PT2M'; // period after which a player is considered inactive
$instanceTimeOut = 'PT5M'; // period after which an instance is considered inactive
$playExpiration = 'PT20S'; // time given to a player to make a move
$practiceExpiration = 'PT2S'; // time between practice player moves
//$moveTimeOut = 'PT20M'; // time when next move is purged from the queue.
$sessionExpiration = 'PT10M'; // time before a session without games or player joining table is expired
/* * ***************************************************************************************************** */
// cheating time outs
$cHeartMarkerTimeOut = 'PT5M';
$cClubMarkerTimeOut = 'PT1M';
$cDiamondMarkerTimeOut = 'PT3M';
$cRiverShufflerTimeOut = 'PT10M'; 
$cPokerPeekerTimeOut = 'PT15M';
$cSocialSpotterTimeOut = 'PT10M'; //60
$cSocialSpotterDuration = 'PT8M'; //45
$cSnakeOilMarkerTimeOut = 'PT30M';
$cSnakeOilMarkerDuration = 'PT20M';
$cAntiOilMarkerTimeOut = 'PT15M';
//$cRiverBendoTimeOut = 'PT10M';

/* * ***************************************************************************************************** */
/* messaging - centralized config parmeters helps with deployment */
$stomp_port = 61613;
//$stomp_host = '192.168.1.70';
$stomp_host = 'cazito.net';
$stomp_exchange = 'direct';// 'player';
$stomp_vhost = '/';
/* * ***************************************************************************************************** */
$amqp_port = 5672;
//$amqp_host = '192.168.1.70';
$amqp_host = 'cazito.net';
$amqp_player_exchange = 'player';
$amqp_session_exchange = 'session';
$amqp_chat_exchange = 'chat';
$qmap_vhost = '/';

// Include Libraries
include_once(dirname(__FILE__) . '/../Libraries/log4php/Logger.php');
include_once(dirname(__FILE__) . '/../Helper/WebServiceDecoder.php');
include_once(dirname(__FILE__) . '/../Helper/DataHelper.php');

// Include Application Scripts
require_once(dirname(__FILE__) . '/Metadata.php');
require_once(dirname(__FILE__) . '/Components/AllInclude.php');
require_once(dirname(__FILE__) . '/DomainHelper/AllInclude.php');
require_once(dirname(__FILE__) . '/DomainEnhanced/AllInclude.php');
require_once(dirname(__FILE__) . '/DomainModel/AllInclude.php');
require_once(dirname(__FILE__) . '/Dto/AllInclude.php');

Logger::configure(dirname(__FILE__) . '/log4php.xml');
$analytics = Logger::getLogger('analytics');
$log = Logger::getLogger('main');

connectToStateDB();
?>
