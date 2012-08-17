<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../Libraries/Helper/WebServiceDecoder.php');
include_once(dirname(__FILE__) . '/../../Libraries/log4php/Logger.php');

// Include Application Scripts
require_once('Config.php');
include_once('Components/EvalHelper.php');
include_once('Data/AllInclude.php');
include_once('DomainModel/AllInclude.php');
include_once('Dto/AllInclude.php');

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
    $playerName = $decodedPar["playerName"];

    /* --------------------------------------------------------------------- */
    $con = connectToStateDB();
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);


    // Logic -----------------------------------------------------------------
    // get or create the user
    $playerDto = EntityHelper::getOrCreatePlayer(null, 0, $playerName, 0, $statusDateTime);
    $gameInstanceStatus = EntityHelper::createPracticeInstance(null, $playerDto->playerId, $statusDateTime);
    $practiceSession = new PracticeSession($gameInstanceStatus->gameInstanceSetup->gameSessionId,
                    $gameInstanceStatus->id, $statusDateTime);

    // populate the DTO with instance information
    $gameInstanceSetupDto = new GameInstanceSetupDto($gameInstanceStatus);

    // player states - requires a logical practice session objecct in order to call the metods
    // the saves are in the database directly
    $practiceSession->savePracticePlayer(0, $playerDto->playerId, $playerName, $statusDateTime);
    $practiceSession->addDummyPlayersAndBlindBets($statusDateTime);

    $gameInstanceSetupDto->userPlayerId = $playerDto->playerId;
    $gameInstanceSetupDto->dealerPlayerId = $practiceSession->playerStatusDTOs[0]->playerId;
    $gameInstanceSetupDto->playerStatusDtos = $practiceSession->playerStatusDTOs;
    $gameInstanceSetupDto->firstPlayerId = $practiceSession->playerStatusDTOs[3]->playerId;
    $gameInstanceSetupDto->blindBets = $practiceSession->blindBets;

    executeSQL("update GameInstance SET DealerPlayerId = $gameInstanceSetupDto->dealerPlayerId,
            FirstPlayerId=$gameInstanceSetupDto->firstPlayerId,
            NextPlayerId=$gameInstanceSetupDto->firstPlayerId, NumberPlayers = $numberSeats
            WHERE id = $gameInstanceStatus->id", __FUNCTION__ . ":
                Error updating practice game instance $gameInstanceStatus->id");
    $pokerCards = EvalHelper::dealAllCards(count($practiceSession->playerStatusDTOs));
    $gameInstanceSetupDto->userPlayerHand = $gameInstanceStatus->gameInstanceSetup->
                    saveGameCardsGetUserHand($pokerCards, $playerDto->playerId);

    $gameInstanceStatus->gameInstanceSetup->saveFirstExpectedMove($gameInstanceSetupDto->firstPlayerId, $defaultTableMin);

    /* --------------------------------------------------------------------- */
    // json-ize
    return json_encode($gameInstanceSetupDto);
}

/* * ************************************************************************************** */

/**
 * Add a user to a casino table. Create the user and table if they do not exist.
 * TODO: validate requesting player with cookie with active sessions
 * FIXME: enforce max number of players
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
    $playerName = $decodedPar["playerName"];
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

    // setup return dto
    $gameStatusDto = new GameStatusDto();

    // Logic --------------------------------------------------------------------------------
    // 1. get or create a new table if it does not exist
    $mustUpdateInstance = false;
    $seatNumber = null;
    $casinoTable = entityHelper::getOrCreateCasinoTable($casinoTableId, $statusDateTime, $tableSize);
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
    $playerDtos = $casinoTable->loadPlayers();
    // a user who accidentally left the browser may come back; see if that's the case
    $found = false;
    for ($i=0, $l=count($playerDtos); $i<$l; $i++) {
        if ($playerDtos[$i]->playerName == $playerName) {
            $found = true;
            $log->debug(__FUNCTION__ . ": Player $playerName came back to casino id $casinoTableId");
            break;
        }
    }
    if (!$found){
        $seatNumber = $casinoTable->findAvailableSeat($playerDtos);
    }
    // 1. get, update or create the player first, so it can be added to the response player list
    $playerDto = EntityHelper::getOrCreatePlayer($casinoTable, $seatNumber, $playerName, 0, $statusDateTime);
    $gameStatusDto->userPlayerId = $playerDto->playerId;
    $gameStatusDto->userSeatNumber = $seatNumber;

    $gameStatusDto->gameStatus = GameStatus::INACTIVE;
    $gameStatusDto->gameSessionId = $casinoTable->currentGameSessionId;
    if ($mustUpdateInstance) {
        // get the game status, player states and community cards
        $gameStatusDto->updateInstanceData($gameInstance);
        $gameStatusDto->communityCards = CardHelper::getCommunityCards($gameInstance->id, $gameInstance->numberCommunityCardsShown);
        if (!is_null($gameInstance->winningPlayerId)) {
            $gameInstance->playerHands = $gameInstance->loadGameInstanceHands();
            $gameStatusDto->gameResultDto = $gameInstance->getGameResult();
        }
        $gameStatusDto->playerStatusDtos = EntityHelper::getPlayerStatusDtosForInstance($gameInstance->id);
        $gameStatusDto->gameStatus = GameStatus::ACTIVE;
    }
    $gameStatusDto->casinoTableId = $casinoTable->id;
    // need to load players again because added one
    $gameStatusDto->waitingListSize = $casinoTable->getWaitingListSize();
    if (!$found) {
        $playerDtos = $casinoTable->loadPlayers();
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::mapPlayerDtos($playerDtos, PlayerStatusType::WAITING);
        $casinoTable->communicateUserJoined($playerDto, $playerDtos, $gameStatusDto->waitingListSize);
    }
    else {
        $gameStatusDto->userSeatNumber = $playerDto->currentSeatNumber;
        if ($gameInstance != null) {
        $gameStatusDto->userPlayerHand = EntityHelper::getUserHand($playerDto->playerId, $gameInstance->id);
        $gameStatusDto->nextMoveDto = EntityHelper::getNextMoveForInstance($gameInstance->id);
        }
    }
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
        $playerDtos = $casinoTable->loadPlayers();
        $gameInstance = $gameSession->startGameInstance($tableSize, $statusDateTime);
    }
    // the player states are saved in the database
    $gameInstance->gameInstanceSetup->resetPlayerStatesAndTurns($casinoTable, $statusDateTime, $lastDealerSeatNumber);
    $gameInstanceSetupDto = new GameInstanceSetupDto($gameInstance);

    // logic common to practice and real games.
    $playerStatuses = EntityHelper::getPlayerInstancesForGame($gameInstance->id);
    $numberPlayers = count($playerStatuses);
    $pokerCards = EvalHelper::dealAllCards($numberPlayers);
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
    $gameInstanceSetupDto->userPlayerHand = $gameInstance->gameInstanceSetup->saveGameCardsGetUserHand($pokerCards, $requestingPlayerId);
    $userPlayerHand = unserialize(serialize($gameInstanceSetupDto->userPlayerHand));
    
    if (!is_null($casinoTable)) {
        // communicate to all playing and waiting players
        $casinoTable->communicateGameStarted($gameInstanceSetupDto, $casinoTable->loadPlayers());
    }
    // restore user play hand which got overwritten.
    $gameInstanceSetupDto->userPlayerHand = $userPlayerHand;
    // --------------------------------------------------------------------------------------
    // json-ize
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

    // Logic --------------------------------------------------------------------------------
    $gameInstance = EntityHelper::getGameInstance($playerAction->gameInstanceId);
    if ($gameInstance->winningPlayerId != null) {
        throw new Exception("Game is ended");
    }

    $casinoTable = EntityHelper::getCasinoTableForSession($gameInstance->gameInstanceSetup->gameSessionId);

    $playerTurn = new PlayerTurn($playerAction, $gameInstance, $statusDateTime);
    $gameInstance = $playerTurn->gameInstanceStatus; // convenience

    $nextPokerMove = $playerTurn->ApplyPlayerAction(); // updates the next player id
    // follow player status update with instance level follow-up
    $playerActionResultDto = $gameInstance->followUpPlayerTurn($nextPokerMove, $playerTurn->action->playerId, $playerTurn->playerInstanceStatus->playerPlayNumber, $statusDateTime);
    $playerActionResultDto->playerStatusDto = new PlayerStatusDto($playerTurn->playerInstanceStatus);

    $gameInstance->communicateMoveResult($playerActionResultDto, 0);

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

    /* --------------------------------------------------------------------- */
    $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
    $waitingListSize = $casinoTable->getWaitingListSize();
    $playerDtos = $casinoTable->loadPlayers();
    $playerDto = $casinoTable->takeSeat($seatNumber, $playerId, $playerDtos, $statusDateTime);
    if (!is_null($playerDto)) {
        // taking a seat affects the player dtos'
        $playerDtos = $casinoTable->loadPlayers();
        $casinoTable->communicateSeatTaken($playerDto, $playerDtos, $waitingListSize);
    }
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

    // Logic -----------------------------------------------------------------
    // same logic as eject player on casinotable, call that instead?
    $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
    if (is_null($casinoTable)) {
        // leaving practice session
        return "{\"page\":\"SafeSaloon\"}";
    }

    $vacatedSeat = $casinoTable->leaveCurrentTable($playerId, $statusDateTime);

    if (is_null($vacatedSeat)) {
        return "{\"page\":\"SafeSaloon\"}";
    }

    $waitingPlayerId = $casinoTable->findNextWaitingPlayer();
    if ($waitingPlayerId) {
        $casinoTable->reserveAndOfferSeat($vacatedSeat, $waitingPlayerId, $statusDateTime);
    }
    $playerDtos = $casinoTable->loadPlayers();
    $leftPlayerDto = new PlayerDto($playerId, null, $defaultAvatarUrl, $vacatedSeat, null);
    $leftPlayerDto->status = PlayerStatusType::LEFT;
    $leftPlayerDto->stake = null;
    $waitingListSize = $casinoTable->getWaitingListSize();
    $casinoTable->communicateUserLeft($leftPlayerDto, $playerDtos, $waitingListSize);
    return "{\"page\":\"SafeSaloon\"}";
}

/* * ************************************************************************************** */
$server->register("startPracticeSession");
$server->register("addUserToCasinoTable");
$server->register("startGame");
$server->register("sendPlayerAction");
$server->register("takeSeat");
$server->register("leaveSaloon");

// fixme: convert to POST
$method = $_GET["method"];
$param = $_GET["param"];
$server->serve($method, $param);

?>