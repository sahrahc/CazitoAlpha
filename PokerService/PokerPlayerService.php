<?php

include_once(dirname(__FILE__) . '/Config.php');

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
    //global $log;

    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["requestingPlayerId"];

    Context::Init();

    // sequencing of poker game start
    $practiceSession = EntityHelper::CreatePracticeSession($playerId);
    $gameInstance = $practiceSession->InitNewPracticeInstance();
    $practiceSession->InitPlayers($playerId, $gameInstance->id);

    // resets the queue for the requesting player and creates one for game session
    QueueManager::addPlayerQueue($playerId, Context::GetQCh());
    QueueManager::addGameSessionQueue($practiceSession->id, Context::GetQCh());

    $blindBetAmounts = $practiceSession->FindBlindBetAmounts();
    $tableSize = $practiceSession->tableMinimum;

    $playerStatuses = $gameInstance->ResetActivePlayers(true);

    $gameInstance->InitInstanceWithDealerAndBlinds($blindBetAmounts, $playerStatuses);
    GameInstanceCards::InitDealGameCards($gameInstance);
    $firstMove = ExpectedPokerMove::InitFirstMoveConstraints($gameInstance, $tableSize, 1);

    // start populating the response
    $gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
    $updatedPlayerStates = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
    $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($updatedPlayerStates, true);
    $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $gameInstance->id);

    $gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($firstMove);
    $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, 3);

    /* --------------------------------------------------------------------- */
    Context::Disconnect();
    /* --------------------------------------------------------------------- */
    // json-ize
    return json_encode($gameStatusDto);
}

/* * ************************************************************************************** */

function CreateTable($par) {
    global $log;
    $decodedPar = json_decode($par, true);
    $tableName = $decodedPar["tableName"];
    $tableSize = $decodedPar["tableSize"];
    $playerId = $decodedPar["requestingPlayerId"];
    if (is_null($tableSize)) {
        return "Error: Table size is required";
    }

    if (is_null($tableName)) {
        return "Error: Table name is required";
    }

    if (is_null($playerId)) {
        return "Error: Player id is required";
    }
    Context::Init();
    // Logic --------------------------------------------------------------------------------
    try {
    $casinoTableDto = TableCoordinator::SetupTable($playerId, $tableName, $tableSize);
    }
    catch(Exception $e) { 
        $log->error($e->getMessage());
        return($e->getMessage());
    }
    Context::Disconnect();
    return json_encode($casinoTableDto);
}

/**
 * Verifies a table exists and provides info to the user. A user does not
 * actually join the table until ready to start playing game.
 */
function GetTable($par) {
    $decodedPar = json_decode($par, true);
    $tableName = $decodedPar["tableName"];
    // TODO: tableCode generated on invite not implemented yet
    $tableCode = $decodedPar["tableCode"];
    $playerId = $decodedPar["requestingPlayerId"];

    if (is_null($tableName)) {
        return "Error: Table name is required";
    }

    if (is_null($tableCode)) {
        return "Error: Table code is required";
    }

    if (is_null($playerId)) {
        return "Error: Player id is required";
    }
    Context::Init();
    // Logic --------------------------------------------------------------------------------
    $casinoTableDto = TableCoordinator::GetTable($playerId, $tableName);

    Context::Disconnect();
    return json_encode($casinoTableDto);
}

/**
 * A user joins a table, table status returned for display and other players
 * notified.
 * @param type $par
 * @return type
 */
function JoinTable($par) {
    $decodedPar = json_decode($par, true);
    $casinoTableId = $decodedPar["casinoTableId"];
    $playerId = $decodedPar["requestingPlayerId"];
    $tableCode = $decodedPar["tableCode"];
    if (is_null($casinoTableId)) {
        return "Error: Table id is required";
    }

    if (is_null($playerId)) {
        return "Error: Player id is required";
    }

    if (is_null($playerId)) {
        return "Error: Table code is required";
    }
    Context::Init();
    // Logic --------------------------------------------------------------------------------
    // 1. get or create a new table if it does not exist
    $casinoTable = EntityHelper::getCasinoTable($casinoTableId);

    // 3. update the player's casino
    $gameStatusDto = TableCoordinator::AddUserToTable($playerId, $casinoTable);
    // --------------------------------------------------------------------------------------

    Context::Disconnect();
    return json_encode($gameStatusDto);
}

/* * ************************************************************************************** */

/**
 * Nothing returned, queue already deleted
 * No option to logout in middle of game, user must leave game first, so
 * no need to cleanup
 */
function logout($par) {
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["requestingPlayerId"];

    // --------------------------------------------------------------------------------------
    connectToStateDB();
    /*    global $dateTimeFormat;
      $statusDateTime = date($dateTimeFormat);
     */
    // 1. get, update or create the player first, so it can be added to the response player list
    PlayerHiddenCard::ResetSleeve($playerId);
    PlayerVisibleCard::ResetVisible($playerId);
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

    Context::Init();
    // --------------------------------------------------------------------------------------
    global $dateTimeFormat;
    $statusDateTime = date($dateTimeFormat);

    // 1. get, update or create the player first, so it can be added to the response player list
    $newPlayer = EntityHelper::getOrCreatePlayer($playerName, $statusDateTime);
    $player = array("userPlayerId" => $newPlayer->id, "playerName" => $newPlayer->name);

    Context::Disconnect();
    return json_encode($player);
}

/* * ************************************************************************************** */

/**
 * FE must call for this. Cannot automatically send info because FE may not be ready.
 */
function cheatLoadSleeve($par) {
    global $log;
    $decodedPar = json_decode($par, true);
    $playerId = $decodedPar["userPlayerId"];
    $cardNameList = $decodedPar["cardNameList"];
    $itemType = $decodedPar["itemType"];
    if ($itemType !== ItemType::LOAD_CARD_ON_SLEEVE) {
        $log->error("");
        return;
    }

    Context::Init();
    PlayerHiddenCard::AddHiddenCards($playerId, $cardNameList);
    $cardNames = CheatingHelper::GetHiddenCards($playerId);

    Context::Disconnect();
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