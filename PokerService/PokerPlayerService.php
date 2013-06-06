<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../libraries/helper/WebServiceDecoder.php');
include_once(dirname(__FILE__) . '/../../libraries/log4php/Logger.php');

// Include Application Scripts
require_once('Config.php');
require_once('Components/EvalHelper.php');
require_once('Components/QueueManager.php');
require_once('DomainHelper/AllInclude.php');
require_once('DomainEnhanced/AllInclude.php');
require_once('DomainModel/AllInclude.php');
require_once('Dto/AllInclude.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/log4php.xml');
$log = Logger::getLogger(__FILE__);

$server = new WebServiceDecoder;

$server->initialize();

/* * ************************************************************************************** */

/**
 * Starts a practice session. If the player is new, create it. This method relies on a lot of hardcoded values such as number of players, practice player id's, initial stakes, seat assignments, etc. An instance is started and returned so that the player can start immediately.
 * Record next move to start countdown.
 * @global timestamp $dateTimeFormat
 * @param json $par { "playerName" : "name" }
 * @return json GameStatusDto
 */
function startPracticeSession($par) {
    global $defaultTableMin;
    global $numberSeats;
    global $log;
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["userPlayerId"];

    $blindBetAmounts = array($defaultTableMin / 2, $defaultTableMin);
        
    Context::Init();

    // sequencing of poker game start
    $practiceSession = EntityHelper::CreatePracticeSession($playerId);
    $practiceSession->InitPlayers($playerId);

    $gameInstance = $practiceSession->InitNewPracticeInstance();
    $playerStatuses = $gameInstance->ResetActivePlayers(true);

    $gameInstance->InitInstanceWithDealerAndBlinds($blindBetAmounts, $playerStatuses);
    GameInstanceCards::InitDealGameCards($gameInstance);
    $firstMove = ExpectedPokerMove::InitFirstMove($gameInstance, $defaultTableMin);

    // start populating the response
    $gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
    $updatedPlayerStates = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
    $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($updatedPlayerStates, true);

    $gameStatusDto->nextMoveDto = new PokerMoveDto($firstMove);
    $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, 3);

    executeSQL("update GameInstance SET DealerPlayerId = $gameStatusDto->dealerPlayerId,
            FirstPlayerId=$firstMove->playerId,
            NextPlayerId=$firstMove->playerId, NumberPlayers = $numberSeats
            WHERE id = $gameInstance->id", __FUNCTION__ . ":
                Error updating practice game instance $gameInstance->id");

    $practiceSession->CommunicateGameStarted($gameStatusDto);

    /* --------------------------------------------------------------------- */
    Context::Disconnect();
    /* --------------------------------------------------------------------- */
    // json-ize
    return json_encode($gameStatusDto);
}

/* * ************************************************************************************** */

/**
 * Add a user to a casino table. Create the user and table if they do not exist.
 * TODO: validate requesting player with cookie with active sessions
 * FIXME: enforce max number of players
 * This and the login service call are REST services, the queue
 * is set up after this service call.
 * @global Logger $log
 * @global timestamp $dateTimeFormat
 * @param json $par = { playerId : 2, isPractice : 0, casinoTableId : 5}
 * @return json $gameStatusDto
 */
function JoinTable($par) {
    global $defaultTableMin;
    global $log;
    $decodedPar = json_decode($par, true);
    $casinoTableId = $decodedPar["casinoTableId"];
    $playerId = $decodedPar["userPlayerId"];
    $tableSize = $decodedPar["tableSize"];
    if (is_null($tableSize)) {
        $tableSize = $defaultTableMin;
    }

    if (is_null($casinoTableId)) {
        $casinoTableId = -1;
    }

    Context::Init();
    // Logic --------------------------------------------------------------------------------
    // 1. get or create a new table if it does not exist
    $casinoTable = EntityHelper::getOrCreateCasinoTable($casinoTableId, $tableSize, $playerId);
    $gameSession = new GameSession($casinoTable->currentGameSessionId);
    /* 2. identify whether valid game session
     * session has instances which are old - isStale = true, start new, don't update
     */
    if ($casinoTable->IsSessionStale()) {
        // note that CasinoTable is updated
        $gameSession = $casinoTable->ResetGameSession($playerId);
    }
    $players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);

    // 3. update the player's casino
    $requestingPlayer = TableCoordinator::AddUserToTable($playerId, $casinoTable, $players);
    // --------------------------------------------------------------------------------------
    // 4. return REST response for newly created user
    $gameStatusDto = GameStatusDto::Init($requestingPlayer, $players, $casinoTable);

    return json_encode($gameStatusDto);
}

/* * ************************************************************************************** */

/**
 * Nothing returned, no need for queues.
 */
function logout($par) {
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["userPlayerId"];

    // --------------------------------------------------------------------------------------
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    // 1. get, update or create the player first, so it can be added to the response player list
    CheatingHelper::ResetSleeve($playerId);
    CheatingHelper::ResetVisible($playerId);
    // no need to send messages, user logging out.
    $status = 'OK';
    return json_encode($status);
}

/**
 * Does not use queues; queues established for poker playing only
 * @global timestamp $dateTimeFormat
 * @param type $par
 * @return custom object 
 */
// TODO: separate sign up from login.
function login($par) {
    $decodedPar = json_decode($par, true);
    $playerName = $decodedPar["playerName"];

    // --------------------------------------------------------------------------------------
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    // 1. get, update or create the player first, so it can be added to the response player list
    $newPlayer = EntityHelper::getOrCreatePlayer($playerName, $statusDateTime);
    $player = array("userPlayerId" => $newPlayer->id, "playerName" => $newPlayer->name);
    return json_encode($player);
}

/* * ************************************************************************************** */

/**
 * FE must call for this. Cannot automatically send info because FE may not be ready.
 */
function cheatLoadSleeve($par) {
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["userPlayerId"];

    Context::SetStatusDT();

    $cardNames = CheatingHelper::GetHiddenCards($playerId);

    //CheatingHelper::communicateCheatingResult($playerId, CheatDtoType::HIDDEN, $cardNames); // message?
    /* --------------------------------------------------------------------- */
    return json_encode($cardNames);
    // TODO: use queue?
}

/* * ************************************************************************************** */
$server->register("startPracticeSession");
$server->register("JoinTable");
$server->register("leaveSaloon");
$server->register("login");
$server->register("logout");
$server->register("cheatLoadSleeve");
/*
  // fixme: convert to POST
  $method = $_GET["method"];
  $param = $_GET["param"];
  $server->serve($method, $param);
  co */
?>