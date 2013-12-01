<?php
header('Content-type: application/json; charset=utf-8');

include_once(dirname(__FILE__) . '/Config.php');
include_once(dirname(__FILE__) . '/../Helper/WebServiceDecoder.php');
$server = new WebServiceDecoder;

$server->initialize();

/* * ************************************************************************************** */

/**
 * Starts a practice session. If the player is new, create it. This method relies on a lot of hardcoded values such as number of players, practice player id's, initial stakes, seat assignments, etc. An instance is started and returned so that the player can start immediately.
 * Record next move to start countdown.
 * @param json $par { "playerName" : "name" }
 * @return json GameStatusDto
 */
function startPracticeSession($par) {
	//global $log;

	$decodedPar = json_decode($par, true);
	$playerId = $decodedPar["requestingPlayerId"];
	// TODO: must secure this, cannot allow non-tester to provide deck
	$indexCards = null;
	if (isset($decodedPar["eventData"])) {
		$indexCards = $decodedPar["eventData"];
	}
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
	$gameCards = new GameInstanceCards($gameInstance->id);
	$gameCards->InitDealGameCards($indexCards);
	$firstMove = ExpectedPokerMove::InitFirstMoveConstraints($gameInstance, $tableSize, 1);

	// start populating the response
	$gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
	$gameStatusDto->playerStatusDtos = PlayerInstance::GetPlayersWithStates($gameInstance->id, null);

	$gameStatusDto->userPlayerId = $playerId;
	$gameStatusDto->userSeatNumber = 0;
	$gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $gameInstance->id);

	$gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($firstMove);

	/* --------------------------------------------------------------------- */
	Context::Disconnect();
	/* --------------------------------------------------------------------- */
	// json-ize
	return json_encode($gameStatusDto);
}

/* * ************************************************************************************** */

function createTable($par) {
	global $log;
	$decodedPar = json_decode($par, true);

	if (!isset($decodedPar["tableSize"])) {
		return "Error: Table size is required";
	}
	if (!isset($decodedPar["tableName"])) {
		return "Error: Table name is required";
	}
	if (!isset($decodedPar["tableCode"])) {
		return "Error: Table name is required";
	}
	if (!isset($decodedPar["requestingPlayerId"])) {
		return "Error: Player id is required";
	}
	$tableName = $decodedPar["tableName"];
	$tableCode = $decodedPar["tableCode"];
	$tableSize = str_replace(',', '', $decodedPar["tableSize"]);
	$playerId = $decodedPar["requestingPlayerId"];
	Context::Init();
	// Logic --------------------------------------------------------------------------------
	try {
		$casinoTableDto = TableCoordinator::SetupTable($playerId, $tableName, $tableCode, $tableSize);
	} catch (Exception $e) {
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
function getTable($par) {
	$decodedPar = json_decode($par, true);

	if (!isset($decodedPar["tableName"])) {
		return "Error: Table name is required";
	}

	if (!isset($decodedPar["tableCode"])) {
		return "Error: Table code is required";
	}

	if (!isset($decodedPar["requestingPlayerId"])) {
		return "Error: Player id is required";
	}
	$tableName = $decodedPar["tableName"];
	// TODO: tableCode generated on invite not implemented yet
	$tableCode = $decodedPar["tableCode"];
	$playerId = $decodedPar["requestingPlayerId"];
	Context::Init();
	// Logic --------------------------------------------------------------------------------
	$casinoTableDto = TableCoordinator::GetTable($playerId, $tableCode);

	Context::Disconnect();
	if ($casinoTableDto === null) {
		return "Cannot find the $tableName table, please try again.";
	}
	
	if ($tableName != $casinoTableDto->casinoTableName) {
		return "You are trying to hack me!";
	}
	return json_encode($casinoTableDto);
}

/* * y
 * A user joins a table, table status returned for display and other players
 * notified.
 * @param type $par
 * @return type
 */


function joinTable($par) {
	$decodedPar = json_decode($par, true);
	if (!isset($decodedPar["requestingPlayerId"])) {
		return "Error: Player id is required";
	}
	$playerId = $decodedPar["requestingPlayerId"];
	if (!isset($decodedPar["tableId"])) {
		return "Error: Table id is required";
	}
	$tableId = $decodedPar["tableId"];
	if (!isset($decodedPar["tableCode"])) {
		return "Error: Table code is required";
	}
	$tableCode = $decodedPar["tableCode"];

	Context::Init();
	// Logic --------------------------------------------------------------------------------
	// 1. get or create a new table if it does not exist
	$casinoTable = EntityHelper::getCasinoTable($tableId);
	if ($casinoTable->code != $tableCode) {
		return "You are trying to hack me!";
	}
	// 3. update the player's casino
	$gameStatusDto = TableCoordinator::AddUserToTable($playerId, $casinoTable);
	// --------------------------------------------------------------------------------------

	// check sleeve
	CheatingHelper::UpdateSleeveSession($playerId, $casinoTable->currentGameSessionId);
	$_SESSION['casinoTableId'] = $casinoTable->id;

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

	$player = EntityHelper::getPlayer($playerId);
	if ($player->currentCasinoTableId !== null) {
		$casinoTable = EntityHelper::getCasinoTable($player->currentCasinoTableId);
		TableCoordinator::RemoveUserFromTable($casinoTable, $playerId);
	}
	//session_start();
	unset($_SESSION['userPlayerId']);
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
	$player = array('userPlayerId' => $newPlayer->id, 'playerName' => $newPlayer->name);

//	session_start();
	$_SESSION['userPlayerId'] = $newPlayer->id;
	Context::Disconnect();
	$response = json_encode($player);
	return $response;
}

/* * ************************************************************************************** */

/**
 * FE must call for this. Cannot automatically send info because FE may not be ready.
 */
function cheatLoadSleeve($par) {
	global $log;
	$decodedPar = json_decode($par, true);
	$itemType = $decodedPar["itemType"];
	$playerId = $decodedPar["userPlayerId"];
	$cardNameList = $decodedPar["cardNameList"];
	$sessionId = $decodedPar["gameSessionId"];
	if ($itemType !== ItemType::LOAD_CARD_ON_SLEEVE) {
		$log->error("");
		return;
	}

	Context::Init();
	// TODO: verify number of cards the user can have
	// TODO: get the number of cards the user already has
	$cheatingItem = new CheatingItem($playerId, $sessionId, $itemType);
	$hItem = new CheatingHidingItem($cheatingItem);
	$cardCodes = $hItem->CheatLoadHidden($cardNameList);

	Context::Disconnect();
	/* --------------------------------------------------------------------- */
	return json_encode($cardCodes);
	// TODO: use queue?
}

function cheatGetSleeve($par) {
	global $log;
	$decodedPar = json_decode($par, true);
	$playerId = $decodedPar["userPlayerId"];

	Context::Init();
	$hiddenCards = new PlayerHiddenCards($playerId, null, ItemType::LOAD_CARD_ON_SLEEVE);
	$cardCodes = $hiddenCards->GetSavedCardCodes();

	Context::Disconnect();
	/* --------------------------------------------------------------------- */
	return json_encode($cardCodes);
	// TODO: use queue?
}

/* * ************************************************************************************** */
/* comment out to run regression tests */

  $server->register("startPracticeSession");
  $server->register("joinTable");
  $server->register("login");
  $server->register("logout");
  $server->register("cheatLoadSleeve");
  $server->register("cheatGetSleeve");
  $server->register("getTable");
  $server->register("createTable");
  // fixme: convert to POST

  $method = $_GET["method"];
  $param = $_GET["param"];
  $server->serve($method, $param);

?>