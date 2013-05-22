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
 * @return json GameInstanceSetupDto
 */
function startPracticeSession($par) {
    global $defaultTableMin;
    global $numberSeats;
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["userPlayerId"];

    /* --------------------------------------------------------------------- */
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

    // Logic -----------------------------------------------------------------
    // get or create the user
    $playerDto = EntityHelper::getPlayerDto($playerId);

    $gameInstanceStatus = EntityHelper::createPracticeInstance(null, $playerDto->playerId, $statusDateTime);
    $practiceSession = new PracticeSession($gameInstanceStatus->gameInstanceSetup->gameSessionId,
                    $gameInstanceStatus->id, $statusDateTime);
    $practiceSession->ex = $ex;
    // populate the DTO with instance information
    $gameInstanceSetupDto = new GameInstanceSetupDto($gameInstanceStatus);

    // player states - requires a logical practice session objecct in order to call the metods
    // the saves are in the database directly
    $practiceSession->savePracticePlayer(0, $playerDto->playerId, $playerDto->playerName, $statusDateTime);
    $practiceSession->addDummyPlayersAndBlindBets($statusDateTime);

    $gameInstanceSetupDto->userPlayerId = $playerDto->playerId;
    $gameInstanceSetupDto->dealerPlayerId = $practiceSession->playerStatusDtos[0]->playerId;
    $gameInstanceSetupDto->playerStatusDtos = $practiceSession->playerStatusDtos;
    $gameInstanceSetupDto->firstPlayerId = $practiceSession->playerStatusDtos[3]->playerId;
    $gameInstanceSetupDto->blindBets = $practiceSession->blindBets;

    executeSQL("update GameInstance SET DealerPlayerId = $gameInstanceSetupDto->dealerPlayerId,
            FirstPlayerId=$gameInstanceSetupDto->firstPlayerId,
            NextPlayerId=$gameInstanceSetupDto->firstPlayerId, NumberPlayers = $numberSeats
            WHERE id = $gameInstanceStatus->id", __FUNCTION__ . ":
                Error updating practice game instance $gameInstanceStatus->id");
    $pokerCards = EvalHelper::shuffleDeck();
    $gameInstanceSetupDto->userPlayerHandDto = $gameInstanceStatus->gameInstanceSetup->
                    saveGameCardsGetUserHandDto($pokerCards, $playerDto->playerId);

    $gameInstanceStatus->gameInstanceSetup->saveFirstExpectedMove($gameInstanceSetupDto->firstPlayerId, $defaultTableMin);

    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    /* --------------------------------------------------------------------- */
    // json-ize
    return json_encode($gameInstanceSetupDto);
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
function addUserToCasinoTable($par) {
    global $numberSeats;
    global $defaultTableMin;
    global $log;
    $decodedPar = json_decode($par, true);
    $casinoTableId = $decodedPar["casinoTableId"];
    $playerId = $decodedPar["userPlayerId"];
    $tableSize = $decodedPar["tableSize"];
    if (is_null($tableSize)) {
        $tableSize = $defaultTableMin;
    }

    // initialize number of players and session info per rules
    // number of players is 4 for practice or increments with each call to this function
    // otherwise (as users get added).
    $numberPlayers = 1;
    if (is_null($casinoTableId)) {
        $casinoTableId = -1;
    }

    // --------------------------------------------------------------------------------------
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

    // setup return dto
    $gameStatusDto = new GameStatusDto();

    // Logic --------------------------------------------------------------------------------
    // 1. get or create a new table if it does not exist
    $mustUpdateInstance = false;
    $seatNumber = null;
    $casinoTable = entityHelper::getOrCreateCasinoTable($casinoTableId, $tableSize, $statusDateTime);
    $casinoTable->ex = $ex; // enable communications
    $gameSession = new GameSession($casinoTable->id, $casinoTable->currentGameSessionId);
    $gameInstance = null;
    /* Business rules for stale session:
     * 1) session with no instances - isStale = null, don't start, don't update
     * 2) session has instances which are old - isStale = true, start new, don't update
     * 3) session has instances that are not old - isStale = false, dont' start, update
     */
    // use case 1: null, don't start, dn't update
    if (is_null($casinoTable->isSessionStale)) {
        $mustUpdateInstance = false;
    }
    // use case 2: true, start new, don't update
    else if ($casinoTable->isSessionStale) {
        $gameSession = $casinoTable->createAndSaveGameSession($statusDateTime);
        $mustUpdateInstance = false;
    }
    // use case 3: false don't start but update
    else if (!$casinoTable->isSessionStale) {
        $gameInstance = EntityHelper::getSessionLastInstance($casinoTable->currentGameSessionId);
        // if none, don't send one back
        $mustUpdateInstance = true;
    }
    $playerDtos = $casinoTable->getCasinoPlayerDtos();
    // a user who accidentally left the browser may come back; see if that's the case
    $found = false;
    for ($i = 0, $l = count($playerDtos); $i < $l; $i++) {
        if ($playerDtos[$i]->playerId == $playerId) {
            $found = true;
            $log->debug(__FUNCTION__ . ": Player " . $playerDtos[$i]->playerName . "
                    came back to casino id $casinoTableId");
            break;
        }
    }
    if (!$found) {
        $seatNumber = $casinoTable->findAvailableSeat($playerDtos);
    }
    // 1. get the player first, so it can be added to the response player list
    $playerDto = EntityHelper::getPlayerDto($playerId);
    if ($playerDto == null) {
        throw new Exception("Invalid user id");
    }
    // 2. update the player's casino
    $playerDto = $casinoTable->addUser($seatNumber, $playerDto, $statusDateTime);
    $gameStatusDto->userPlayerId = $playerDto->playerId;
    $gameStatusDto->userSeatNumber = $seatNumber;

    $gameStatusDto->gameStatus = GameStatus::INACTIVE;
    $gameStatusDto->gameSessionId = $casinoTable->currentGameSessionId;
    if ($mustUpdateInstance) {
        // get the game status, player states and community cards
        $gameStatusDto->updateInstanceData($gameInstance);
        $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, $gameInstance->numberCommunityCardsShown);
        if (!is_null($gameInstance->winningPlayerId)) {
            $gameInstance->playerHands = $gameInstance->getInstancePlayerHandDtos();
            $gameStatusDto->gameResultDto = $gameInstance->getGameResult();
        }
        $gameStatusDto->playerStatusDtos = EntityHelper::getPlayerStatusDtosForInstance($gameInstance->id);
        $gameStatusDto->gameStatus = GameStatus::ACTIVE;
    }
    $gameStatusDto->casinoTableId = $casinoTable->id;
    // need to load players again because added one
    $gameStatusDto->waitingListSize = $casinoTable->getWaitingListSize();
    if (!$found) {
        $playerDtos = $casinoTable->getCasinoPlayerDtos();
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::mapPlayerDtos($playerDtos, PlayerStatusType::WAITING);
        $casinoTable->communicateUserJoined($playerDto, $playerDtos, $gameStatusDto->waitingListSize);
    } else {
        $gameStatusDto->userSeatNumber = $playerDto->currentSeatNumber;
        if (!is_null($gameInstance)) {
            $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerDto->playerId, $gameInstance->id);
            $gameStatusDto->nextMoveDto = EntityHelper::getNextMoveForInstance($gameInstance->id);
        }
    }
    
    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    // --------------------------------------------------------------------------------------
    // json-ize
    return json_encode($gameStatusDto);
}

/* * ************************************************************************************** */

/**
 * Start a new game, set blind bets, identify dealer and first player. Get current list of seated players from the casino table to identify or reset turn numbers, which must be sequential starting at zero, at the beginning of each game
 * TODO: validate requesting player with cookie with active sessions
 * @global Logger $log
 * @global timestamp $dateTimeFormat
 * @param type $par = { gameSessionId : 2, requestingPlayerId : 0}
 * @return json gameInstanceSetupDto
 */
function startGame($par) {
    global $numberSeats;
    global $defaultTableMin;
    global $log;
    $decodedPar = json_decode($par, true);
    $gameSessionId = $decodedPar["gameSessionId"];
    $requestingPlayerId = $decodedPar["requestingPlayerId"];
    $isPractice = $decodedPar["isPractice"];
    $tableSize = $decodedPar["tableSize"];
    if (is_null($tableSize)) {
        $tableSize = $defaultTableMin;
    }
    /* --------------------------------------------------------------------- */
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    // queue must have already been declared
    // Logic -----------------------------------------------------------------
    // find the previous dealer if any
    $gameInstance = null;
    $casinoTable = null;
    $lastDealerSeatNumber = null;
    $numberPlayers = null;
    /* clean up previous instance */
    $previousInstance = EntityHelper::getSessionLastInstance($gameSessionId);
    if (!is_null($previousInstance)) {
        executeSQL("UPDATE NextPokerMove SET IsDeleted = 1 WHERE gameInstanceId = 
                $previousInstance->id", __FUNCTION__ . "
                : Error deleting previous instance id $previousInstance->id moves");
        $lastDealerSeatNumber = $previousInstance->gameInstanceSetup->dealerTurnNumber;
        $numberPlayers = $previousInstance->gameInstanceSetup->numberPlayers;
    }
    if ($isPractice) {
        $blindBetAmounts = array($tableSize / 2, $tableSize);
        $gameInstance = EntityHelper::createPracticeInstance($gameSessionId, $requestingPlayerId, $statusDateTime);
        $log->debug(__FUNCTION__ . " - game session id created " . $gameSessionId);
        $gameSession = new PracticeSession($gameSessionId, $gameInstance->id, $statusDateTime);
        if (is_null($numberPlayers)) {
            $numberPlayers = $numberSeats;
        }
    } else {
        $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
        $blindBetAmounts = $casinoTable->findBlindBetAmounts();
        $tableSize = $casinoTable->tableMinimum;
        $gameSession = new GameSession($casinoTable->id, $casinoTable->currentGameSessionId);
        // the instance is created now so that the id is available but there is not much info
        // save previous dealer seat
        $playerDtos = $casinoTable->getCasinoPlayerDtos();
        $gameInstance = $gameSession->startGameInstance($tableSize, $statusDateTime);
    }
    $gameSession->ex = $ex;
    // the player states are saved in the database
    $gameInstance->gameInstanceSetup->resetPlayerStatesAndTurns($casinoTable, $statusDateTime, $lastDealerSeatNumber);
    $gameInstanceSetupDto = new GameInstanceSetupDto($gameInstance);

    // logic common to practice and real games.
    $playerStatuses = EntityHelper::getPlayerInstancesForGame($gameInstance->id);
    $pokerCards = EvalHelper::shuffleDeck();
    $log->Debug(__FUNCTION__ . " - number cards: " . count($pokerCards));

    $blindBets = $gameInstance->saveInstanceWithDealerAndBlinds($blindBetAmounts, $lastDealerSeatNumber, $playerStatuses, $statusDateTime);
    $gameInstanceSetupDto->gameInstanceId = $gameInstance->id;
    $gameInstanceSetupDto->userPlayerId = $requestingPlayerId;
    $gameInstanceSetupDto->dealerPlayerId = $gameInstance->gameInstanceSetup->dealerPlayerId;
    $gameInstanceSetupDto->firstPlayerId = $gameInstance->gameInstanceSetup->firstPlayerId;
    $gameInstanceSetupDto->blindBets = $blindBets;
    $gameInstanceSetupDto->playerStatusDtos = EntityHelper::getPlayerStatusDtosForInstance($gameInstance->id);
    // if practice instance created with new values
    //$gameInstance->savePlayerHands();

    $gameInstance->gameInstanceSetup->saveFirstExpectedMove($gameInstanceSetupDto->firstPlayerId, $tableSize);
    $gameInstanceSetupDto->userPlayerHandDto = $gameInstance->gameInstanceSetup->saveGameCardsGetUserHandDto($pokerCards, $requestingPlayerId);
    $userPlayerHand = unserialize(serialize($gameInstanceSetupDto->userPlayerHandDto));

    // communicate to all playing and waiting players
    if (is_null($casinoTable)) {
        $gameSession->communicateGameStarted($gameInstanceSetupDto, $statusDateTime);
    } else {
        $gameSession->communicateGameStarted($gameInstanceSetupDto, $casinoTable->getCasinoPlayerDtos(), $statusDateTime);
    }
    $msg = CheatingHelper::revealMarkedCards($gameInstance);
    if (!is_null($msg)) {
        QueueManager::communicateCheatingEvent($ex, $playerId, $gameSessionId, $msg->eventType, $msg->log);
        if (!is_null($msg->eventType)) {
            QueueManager::communicateCheatingInfo($ex, $playerId, $gameSessionId, $msg->logType, $msg->eventData);
        }
    }

    // restore user play hand which got overwritten.
    $gameInstanceSetupDto->userPlayerHandDto = $userPlayerHand;
    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    // --------------------------------------------------------------------------------------
    // testing only! MUST ENABLE QUEUES in PHP
    return json_encode($gameInstanceSetupDto);
}

/* * ************************************************************************************** */

/**
 * TODO: validate requesting player with cookie with active sessions. Validation means retrieving the last action and getting the correct value for now, trust the browser sends the correct data validate also that the user making the action had the turn, there should be no concurrency issues.
 * @global timestamp $dateTimeFormat
 * @param PlayerActionDto $par
 * @return PlayerActionResultDto
 */
function sendPlayerAction($par) {
    global $numberSeats;

    $playerAction = json_decode($par);

    // --------------------------------------------------------------------------------------
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);
    $con = connectToStateDB();

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    // queue must have already been declared
    
    // Logic --------------------------------------------------------------------------------
    $gameInstance = EntityHelper::getGameInstance($playerAction->gameInstanceId);
    $gameInstance->ex = $ex;
    if (!is_null($gameInstance->winningPlayerId)) {
        throw new Exception("Game is ended");
    }

    $casinoTable = EntityHelper::getCasinoTableForSession($gameInstance->gameInstanceSetup->gameSessionId);

    $playerTurn = new PlayerTurn($playerAction, $gameInstance, $statusDateTime);
    //$gameInstance = $playerTurn->gameInstanceStatus; // convenience

    $nextPokerMove = $playerTurn->applyPlayerAction(); // updates the next player id
    // follow player status update with instance level follow-up
    $playerActionResultDto = $gameInstance->followUpPlayerTurn($nextPokerMove, $playerTurn->action->playerId, $playerTurn->playerInstanceStatus->playerPlayNumber, $statusDateTime);
    $playerActionResultDto->playerStatusDto = new PlayerStatusDto($playerTurn->playerInstanceStatus);

    $gameInstance->communicateMoveResult($playerActionResultDto, 0);

    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    // --------------------------------------------------------------------------------------
    // json-ize
    return json_encode($playerActionResultDto);
}

/* * ************************************************************************************** */

function takeSeat($par) {
    global $log;
    $decodedPar = json_decode($par, true);
    $gameSessionId = $decodedPar["gameSessionId"];
    $playerId = $decodedPar["playerId"];
    $seatNumber = $decodedPar["seatNumber"];

    // --------------------------------------------------------------------------------------
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);
    $con = connectToStateDB();

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    // queue must have already been declared

    /* --------------------------------------------------------------------- */
    $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
    $casinoTable->ex = $ex;
    $waitingListSize = $casinoTable->getWaitingListSize();
    $playerDtos = $casinoTable->getCasinoPlayerDtos();
    $playerDto = $casinoTable->takeSeat($seatNumber, $playerId, $playerDtos, $statusDateTime);
    if (!is_null($playerDto)) {
        // taking a seat affects the player dtos'
        $playerDtos = $casinoTable->getCasinoPlayerDtos();
        $casinoTable->communicateSeatTaken($playerDto, $playerDtos, $waitingListSize);
    }
    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    /* --------------------------------------------------------------------- */
    return json_encode($playerDto);
}

/* * ************************************************************************************** */

function leaveSaloon($par) {
    global $log;
    global $defaultAvatarUrl;

    $decodedPar = json_decode($par, true);
    $gameSessionId = $decodedPar["gameSessionId"];
    $playerId = $decodedPar["playerId"];

    /* --------------------------------------------------------------------- */
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    // queue must have already been declared
 
    // Logic -----------------------------------------------------------------
    // same logic as eject player on casinotable, call that instead?
    $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
    if (is_null($casinoTable)) {
        // leaving practice session
        return "{\"page\":\"SeedySaloon\"}";
    }
    $casinoTable->ex = $ex;

    $vacatedSeat = $casinoTable->leaveCurrentTable($playerId, $statusDateTime);

    if (is_null($vacatedSeat)) {
        return "{\"page\":\"SeedySaloon\"}";
    }

    $waitingPlayerId = $casinoTable->findNextWaitingPlayer();
    if ($waitingPlayerId) {
        $casinoTable->reserveAndOfferSeat($vacatedSeat, $waitingPlayerId, $statusDateTime);
    }
    // reset sleeves and visible cards
    CheatingHelper::resetSleeve($playerId);
    // no need to send messages, user leaving table.
    // ******************************
    $playerDtos = $casinoTable->getCasinoPlayerDtos();
    // FIXME: should this be playerstatusdto?
    $leftPlayerDto = new PlayerDto($playerId, null, $defaultAvatarUrl, null);
    $leftPlayerDto->currentSeatNumber = $vacatedSeat;
    $leftPlayerDto->status = PlayerStatusType::LEFT;
    $leftPlayerDto->stake = null;
    $waitingListSize = $casinoTable->getWaitingListSize();
    $casinoTable->communicateUserLeft($leftPlayerDto, $playerDtos, $waitingListSize);

    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    /* --------------------------------------------------------------------- */
    return "{\"page\":\"SeedySaloon\"}";
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
    CheatingHelper::resetSleeve($playerId);
    CheatingHelper::resetVisible($playerId);
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
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    // 1. get, update or create the player first, so it can be added to the response player list
    $playerDto = EntityHelper::getOrCreatePlayer($playerName, $statusDateTime);
    $player = array("userPlayerId" => $playerDto->playerId, "playerName" => $playerDto->playerName);
    return json_encode($player);
}
/* * ************************************************************************************** */

function cheat($par) {
    global $log;
    global $defaultAvatarUrl;

    $cheatRequestDto = json_decode($par);

    /* --------------------------------------------------------------------- */
    $con = connectToStateDB();
    global $dateTimeFormat;
    $currentDate = new DateTime();
        
    $dateString = $currentDate->format($dateTimeFormat);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    // queue must have already been declared
    
    $playerId = $cheatRequestDto->userPlayerId;
    // cheating items before user enters session
    switch ($cheatRequestDto->itemType) {
        case ItemType::LOAD_CARD_ON_SLEEVE:
            $cardNameList = $cheatRequestDto->cardNameList;
            $returnDto = CheatingHelper::addHiddenCards($playerId, $cardNameList);
            return json_encode($returnDto);
        case ItemType::SOCIAL_SPOTTER:
            $sessionId = $cheatRequestDto->gameSessionId;
            $msg = CheatingHelper::startCardMarking($playerId, $sessionId, $dateString);
            $msg->eventData = 'OK';
        QueueManager::communicateCheatingEvent($ex, $playerId, $sessionId, $msg->logType, $msg->log);
            return json_encode($msg->eventData);
            // remove when queue used by client, only sent because response needed for REST call
    }

    $gameInstance = EntityHelper::getGameInstance($cheatRequestDto->gameInstanceId);
    if (is_null($gameInstance)) {
        $gameInstance = EntityHelper::getSessionLastInstance($cheatRequestDto->gameSessionId);
    }
    if (is_null($gameInstance)) {
        return null;
    }
    // convenience vars
    $gameInstanceId = $gameInstance->id;
    $gameSessionId = $gameInstance->gameInstanceSetup->gameSessionId;
    // Logic -----------------------------------------------------------------
    $returnDto = null;
    
    switch ($cheatRequestDto->itemType) {
        case ItemType::ACE_PUSHER:
            $playerCardNumber = $cheatRequestDto->playerCardNumber;
            $returnDto = CheatingHelper::pushRandomAce($playerId, $gameInstance, $playerCardNumber, $currentDate);
            break;
        case ItemType::HEART_MARKER:
            $returnDto = CheatingHelper::getSuitForAllGameCards($playerId, $gameInstance, 'hearts', $currentDate);
            break;
        case ItemType::CLUB_MARKER:
            $returnDto = CheatingHelper::getSuitForAllGameCards($playerId, $gameInstance, 'clubs', $currentDate);
            break;
        case ItemType::DIAMOND_MARKER:
            $returnDto = CheatingHelper::getSuitForAllGameCards($playerId, $gameInstance, 'diamonds', $currentDate);
            break;
        case ItemType::RIVER_SHUFFLER:
            $returnDto = CheatingHelper::cheatLookRiverCard($playerId, $gameInstance, $dateString);
            break;
        case ItemType::RIVER_SHUFFLER_USE:
            $returnDto = CheatingHelper::cheatSwapRiverCard($playerId, $gameInstance);
            break;
        default:
            break;
    }
    /* --------------------------------------------------------------------- */
    if (!is_null($msg)) {
        QueueManager::communicateCheatingEvent($ex, $playerId, $gameSessionId, $msg->eventType, $msg->log);
        if (!is_null($msg->eventType)) {
            // optional for RIVER_SHUFFLER, none for RIVER_SHUFFLER_USE
            QueueManager::communicateCheatingInfo($ex, $playerId, $gameSessionId, $msg->logType, $msg->eventData);
        }
    }
    QueueManager::disconnect($qConn);
    /* --------------------------------------------------------------------- */
    if (is_null($returnDto)){
        $returnDto = 'OK';
    }
    return json_encode($returnDto);
}

/**
 * FE must call for this. Cannot automatically send info because FE may not be ready.
 */
function cheatLoadSleeve($par) {
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["userPlayerId"];

    $con = connectToStateDB();
    
    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    // queue must have already been declared

    $msg = CheatingHelper::getHiddenCards($playerId);

    QueueManager::communicateCheatingInfo($ex, $playerId, $gameSessionId, $msg->logType, $msg->eventData);// message?
    /* --------------------------------------------------------------------- */
    QueueManager::disconnect($qConn);
    /* --------------------------------------------------------------------- */
    return json_encode($msg->eventData);
    // TODO: use queue?
}

/* * ************************************************************************************** */
$server->register("startPracticeSession");
$server->register("addUserToCasinoTable");
$server->register("startGame");
$server->register("sendPlayerAction");
$server->register("takeSeat");
$server->register("leaveSaloon");
$server->register("cheat");
$server->register("login");
$server->register("logout");
$server->register("cheatLoadSleeve");
/*
  // fixme: convert to POST
  $method = $_GET["method"];
  $param = $_GET["param"];
  $server->serve($method, $param);
co*/
?>