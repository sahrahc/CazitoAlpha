<?php

/* * ************************************************************************************* */

class HighestHand {

	public $handCategory;
	public $rankWithinCategory;
	public $winningPlayerId;

}

class GameInstance {

	public $id;
	// setup
	public $gameSessionId;
	public $status;
	public $startDateTime;
	public $lastUpdateDateTime;
	public $numberPlayers;
	public $dealerPlayerId;
	public $firstPlayerId;
	public $nextPlayerId;
	public $currentPotSize;
	public $lastBetSize;
	public $numberCommunityCardsShown;
	public $lastInstancePlayNumber;
	// if game over
	public $winningPlayerId;
	public $playerHands;
	private $history;

	public function __construct($id) {
		$this->history = Logger::getLogger(__CLASS__);
		$this->id = $id == null ? null : (int) $id;
	}

	/**
	 * Get the game instance object given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
	 * @param int $gInstId
	 * @return GameInstance
	 */
	public static function GetGameInstance($gInstId) {
		global $dateTimeFormat;
		if (is_null($gInstId)) {
			return null;
		}

		// left join on casino table and practice session
		$query = "SELECT g.* 
                FROM GameInstance g
                WHERE g.Id = $gInstId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}

		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$obj = new GameInstance($row["Id"]);
		$obj->gameSessionId = $row["GameSessionId"] == null ? null : (int) $row["GameSessionId"];
		$obj->status = $row["Status"];
		$obj->startDateTime = $row["StartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["StartDateTime"]);
		$obj->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
		$obj->numberPlayers = $row["NumberPlayers"] == null ? null : (int) $row["NumberPlayers"];
		$obj->dealerPlayerId = $row["DealerPlayerId"] == null ? null : (int) $row["DealerPlayerId"];
		$obj->firstPlayerId = $row["FirstPlayerId"] == null ? null : (int) $row["FirstPlayerId"];
		$obj->nextPlayerId = $row["NextPlayerId"] == null ? null : (int) $row["NextPlayerId"];
		$obj->currentPotSize = $row["CurrentPotSize"] == null ? null : (int) $row["CurrentPotSize"];
		$obj->lastBetSize = $row["LastBetSize"] == null ? null : (int) $row["LastBetSize"];
		$obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'] == null ? null : (int) $row['NumberCommunityCardsShown'];
		$obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'] == null ? null : (int) $row['LastInstancePlayNumber'];
		$obj->winningPlayerId = $row['WinningPlayerId'] == null ? null : (int) $row['WinningPlayerId'];

		return $obj;
	}

	/*
	 * Resets the active players data structures to account for players who 
	 * left in the middle new ones taking their place. Use status only
	 * Player turn numbers increase with seat number order.
	 */

	public function ResetActivePlayers($isPractice = false) {
		// Delete players who left the casino table
		if (!$isPractice) {
			PlayerInstance::DeleteDeparted($this->gameSessionId);
			// Get players with same gamesessionid and seat number
			// who don't have player state
			$newPlayerStatuses = PlayerInstance::GetNewPlayerStatesOnSession($this);
			if (!is_null($newPlayerStatuses)) {
				foreach ($newPlayerStatuses as $playerStatus) {
					$playerStatus->Insert();
				}
			}
		}
		//-------------------------------------------------------------
		// update game instance id for remaining players
		//-------------------------------------------------------------
		// Get all player instances for the game session, after adding/removing
		// game instance id updated when reset below
		$nextDealerSN = $this->_getNextDealerSeatNumber();
		$playerStatuses = PlayerInstance::GetPlayerInstancesForNewGame($this->gameSessionId, $nextDealerSN);

		// assign turn numbers and update; note that players who just joined the table will have both an insert and an update
		$countPlayers = count($playerStatuses);
		for ($i = 0; $i < $countPlayers; $i++) {
			// first turn is for user left to the one who placed blind
			$playerStatuses[$i]->gameInstanceId = $this->id;
			$playerStatuses[$i]->turnNumber = $i; //$turnNumber;
			// initialize
			$playerStatuses[$i]->status = PlayerStatusType::WAITING;
			$playerStatuses[$i]->lastPlayAmount = 0;
			$playerStatuses[$i]->lastPlayInstanceNumber = 0;
			$playerStatuses[$i]->numberTimeOuts = 0;
			$playerStatuses[$i]->Update();
		}
		return PlayerInstance::GetPlayerInstancesForGame($this->id);
	}

	/**
	 * When a game is first started, the dealer and blinds are identified based on the dealer of the last instance.
	 * Must be called after turns reset.
	 * @param array(int, int) blindAmts The size of the small and large blinds.
	 * @param PlayerInstance[] $pStatuses The index is the turn number because reset makes them so.
	 * @param timestamp statusDT
	 */
	function InitInstanceWithDealerAndBlinds($blindAmts, $pStatuses) {
		$blind1 = $blindAmts[0];
		$blind2 = $blindAmts[1];

		$statusDT = Context::GetStatusDT();
		$count = count($pStatuses);
		// dealer is always turn 0 but need modulus because number of seats
		// may result in less turns than roles (e.g., 2 players)
		$dealerTurn = 0 % $count; // for symmetry
		$blind1Turn = 1 % $count;
		$blind2Turn = 2 % $count;
		$nextPlayerTurn = 3 % $count;

		$playerTurn1 = PlayerInstance::GetPlayerInstanceByTurn($this->id, $blind1Turn);
		$playerTurn1->currentStake -= $blind1;
		$playerTurn1->status = PlayerStatusType::BLIND_BET;
		$playerTurn1->lastPlayAmount = $blind1;
		$playerTurn1->Update();
		$playerTurn2 = PlayerInstance::GetPlayerInstanceByTurn($this->id, $blind2Turn);
		$playerTurn2->currentStake -= $blind2;
		$playerTurn2->status = PlayerStatusType::BLIND_BET;
		$playerTurn2->lastPlayAmount = $blind2;
		$playerTurn2->Update();

		$this->nextPlayerId = $pStatuses[$nextPlayerTurn]->playerId;
		//$this->nextTurnNumber = $nextPlayerTurn;
		$this->dealerPlayerId = $pStatuses[$dealerTurn]->playerId;
		//$this->dealerTurnNumber = $dealerTurn;
		$this->firstPlayerId = $this->nextPlayerId;

		$this->lastBetSize = $blind2;
		$this->currentPotSize = $blind1 + $blind2;
		$this->numberPlayers = $count;
		$this->lastUpdateDateTime = $statusDT;

		$updateDTString = Context::GetStatusDTString();
		$vars = "LastUpdateDateTime, NumberPlayers, DealerPlayerId, FirstPlayerId, NextPlayerId, "
				. "CurrentPotSize, LastBetSize, NumberCommunityCardsShow, LastInstancePlayNumber";
		$values = "'$updateDTString', $this->numberPlayers, $this->dealerPlayerId, "
				. "$this->firstPlayerId, $this->nextPlayerId, $this->currentPotSize, "
				. "$this->lastBetSize, $this->numberCommunityCardsShown, "
				. "$this->lastInstancePlayNumber";
		$where = "Id = $this->id";
		$event = "UPDATE GameInstance SET "
				. "LastUpdateDateTime = '$updateDTString',"
				. "NumberPlayers = $this->numberPlayers,"
				. "DealerPlayerId = $this->dealerPlayerId, "
				. "FirstPlayerId = $this->firstPlayerId, "
				. "NextPlayerId = $this->nextPlayerId, "
				. "CurrentPotSize = $this->currentPotSize, "
				. "LastBetSize = $this->lastBetSize, "
				. "NumberCommunityCardsShown = $this->numberCommunityCardsShown, "
				. "LastInstancePlayNumber = $this->lastInstancePlayNumber "
				. "WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	/**
	 * After a player makes a move, this operation is called to retrieve 
	 * the constraints on the next player's
	  moves. The next move is saved so that it can be expired.
	 * Restrictions: updateNextPlayerIdAndTurn must have been called before.
	 * @return ExpectedPokerMove

	  public function FindNextExpectedMove($curTurn) {
	  global $playExpiration;
	  global $practiceExpiration;
	  $nextPokerMove = new ExpectedPokerMove();

	  $this->_getNextPlayerIdAndTurn($curTurn);
	  $expirationDateTime = new DateTime();
	  if ($this->isNextPlayerVirtual == 1) {
	  $expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
	  } else {
	  $expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
	  }

	  // 2 - parse out the next move
	  // no need to set identifier, auto incrementing id
	  $nextPokerMove->gameInstanceId = $this->id;
	  $nextPokerMove->playerId = $this->nextPlayerId;
	  $nextPokerMove->expirationDate = $expirationDateTime;

	  // find move sizes - instance has not updated yet.
	  // see if check allowed ---------------------------------------
	  // Rule: not allowed on first round except for player who placed blind bet.
	  $nextPokerMove->checkAmount = null;
	  if ($this->lastInstancePlayNumber >= $this->numberPlayers - 1) {
	  $nextPokerMove->checkAmount = 0;
	  }
	  // call size  -----------------------------------------------
	  $nextPokerMove->callAmount = $this->lastBetSize;

	  // see how much raise is enabled by ---------------------------
	  // Rule: first player on first round can only raise by 2*bigblind, but that is taken
	  // care of on initFirstMove.
	  $nextPokerMove->raiseAmount = $this->tableMinimum + $this->lastBetSize;

	  $nextPokerMove->Insert();
	  return $nextPokerMove;
	  }
	 * 
	 */

	/**
	 * Find the winner and everyone's hands at the end of the game.
	 * @param type $statusDT
	 */
	function FindWinner(&$playerStatuses) {
		$statusDT = Context::GetStatusDT();
		// gets all the game cards for the instance
		$gameCards = new GameInstanceCards($this->id);
		// true means exclude folded
		$gameCards->GetSavedCards(true);
		$playerHands = $gameCards->playerHands;

		$hH = new HighestHand(); // holds the highest hand found when traversing the players
		$hH->handCategory = -1;
		$hH->rankWithinCategory = -1;
		$hH->winningPlayerId = -1;

		$ccs = array(
			$gameCards->communityCards[0]->cardIndex,
			$gameCards->communityCards[1]->cardIndex,
			$gameCards->communityCards[2]->cardIndex,
			$gameCards->communityCards[3]->cardIndex,
			$gameCards->communityCards[4]->cardIndex);

		for ($i = 0; $i < count($playerHands); $i++) {
			$cards = $ccs;
			array_push($cards, $gameCards->playerHands[$i]->pokerCard1->cardIndex);
			array_push($cards, $gameCards->playerHands[$i]->pokerCard2->cardIndex);
			//$playerHands[$i]->pokerHandType = 
			CardHelper::identifyPlayerHand($cards, $playerHands[$i]);

			$hH = CardHelper::getHigherCard($hH, $playerHands[$i]);
			$playerHands[$i]->Update();
		}

		// update player statuses.
		for ($i = 0; $i < count($playerStatuses); $i++) {
			// update player statuses too
			if ($playerStatuses[$i]->playerId === $hH->winningPlayerId) {
				$playerStatuses[$i]->status = PlayerStatusType::WON;
				$playerStatuses[$i]->currentStake += $this->currentPotSize;
			} else {
				if ($playerStatuses[$i]->status !== PlayerStatusType::LEFT &&
						$playerStatuses[$i]->status !== PlayerStatusType::SEATED) {
					$playerStatuses[$i]->status = PlayerStatusType::LOST;
				}
			}
			$playerStatuses[$i]->Update();
		}
		// TODO: a little inefficient, status, stake on playerhand saved to db,
		// but playerstate is needed for DTO out.
		/* 		for ($i = 0; $i < count($playerHands); $i++) {
		  // update hand for each player
		  if ($playerHands[$i]->playerId == $hH->winningPlayerId) {
		  $playerHands[$i]->isWinningHand = 1;
		  } else {
		  $playerHands[$i]->isWinningHand = 0;
		  }
		  // update player statuses too
		  if ($playerHands[$i]->playerId === $hH->winningPlayerId) {
		  $playerHands[$i]->status = PlayerStatusType::WON;
		  $playerHands[$i]->currentStake = $playerStatuses[$i]->currentStake;
		  } else {
		  $playerHands[$i]->status = PlayerStatusType::LOST;
		  }
		  $playerHands[$i]->Update();
		  } */
		// save winning player Id
		$this->lastUpdateDateTime = $statusDT;
		$this->winningPlayerId = $hH->winningPlayerId;
		$this->playerHands = $playerHands;
		/* --------------------------------------------------------------------- */
	}

	/**
	 * Checks whether the end of the round is reached (need to deal community
	 * cards in that case)
	 * Returns the round number that ended, null otherwise
	 */
	public function IsRoundEnd() {
		$numberPlayers = $this->numberPlayers;
		$instancePlayNumber = $this->lastInstancePlayNumber;
		switch ($instancePlayNumber) {
			case $numberPlayers:
				return 1;
			case $numberPlayers * 2:
				return 2;
			case $numberPlayers * 3:
				return 3;
			case $numberPlayers * 4:
				return 4;
		}
		return null;
	}

	/**
	 * Gets the game result if ended
	 */

	/**
	 * Checks whether the end of the game was reached on database.
	 * @return bool
	 */
	public function IsGameEnded() {
		if (!is_null($this->winningPlayerId)) {
			return true;
		}
		// see if current player's move triggered and end date before calculating the next.
		// 1 - if only on user remaining (status - not folded) then end game
		$folded = PlayerStatusType::FOLDED;
		$left = PlayerStatusType::LEFT;
		$query = "SELECT count(1) FROM PlayerState WHERE GameInstanceId =
            $this->id AND Status != '$folded' AND Status != '$left'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$row = mysql_fetch_array($result, MYSQL_NUM);
		if ($row[0] <= 1) {
			return true;
		}
		// if next player and previous player play is fourth, end game.
		// instance has been updated yet by the calling function
		if ($this->lastInstancePlayNumber >= $this->numberPlayers * 4) {
			return true;
		}
		return false;
	}

	/**
	 * Create an message and send it to everyone's queue including the player who made the move.
	 * @param type $isTimeOut Whether the action was by a player or triggered by timeout
	 */
	function CommunicateMoveResult($gameStatusDto) {
		$ex = Context::GetExchangePlayer();

		// a timeout by a practice game is not a time out.
		$actionType = EventType::ChangeNextTurn;

		// get the players;
		$players = null;
		$gameSession = GameSession::GetGameSession($gameStatusDto->gameSessionId);
		if ($gameSession->isPractice == 1) {
			// for practice game, get non-virtual player only
			// by convention first player on list, but not using convention
			$players = array(Player::GetPracticePlayer($this->id));
		} else {
			$casinoTable = CasinoTable::GetCasinoTableForSession($this->gameSessionId);
			$players = Player::GetPlayersForCasinoTable($casinoTable->id);
		}
		for ($i = 0; $i < count($players); $i++) {

			if ($players[$i]->isVirtual == 1 ||
					$players[$i] == PlayerStatusType::LEFT) {
				continue;
			}
			$message = new QueueMessage($actionType, $gameStatusDto, $this->gameSessionId);
			QueueManager::SendToPlayer($ex, $players[$i]->id, json_encode($message));
		}
	}

	/*	 * ***************************************************************************** */
	/* Entity functions */

	/**
	 * Gets the seat number for the last active instance of a game session
	 * @param type $gameSessionId
	 */
	private function _getNextDealerSeatNumber() {
		$query = "SELECT SeatNumber 
            FROM GameInstance i 
            INNER JOIN PlayerState ps on i.DealerPlayerId = ps.PlayerId
            WHERE i.GameSessionId = $this->gameSessionId
                ORDER BY i.Id DESC LIMIT 1";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			$lastDealerSN = -1;
		} else {
			$row = mysql_fetch_array($result, MYSQL_NUM);
			$lastDealerSN = $row[0];
		}
		$nextDealerResult = executeSQL("SELECT SeatNumber
			FROM PlayerState
            WHERE GameSessionId = $this->gameSessionId "
				. "AND SeatNumber > $lastDealerSN ORDER BY SeatNumber LIMIT 1", __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($nextDealerResult) == 0) {
			$nextDealerResult = executeSQL("SELECT SeatNumber
			FROM PlayerState
            WHERE GameSessionId = $this->gameSessionId "
					. "AND SeatNumber >= 0 ORDER BY SeatNumber LIMIT 1", __CLASS__ . "-" . __FUNCTION__);
		}
		$nextDealerRow = mysql_fetch_array($nextDealerResult, MYSQL_NUM);
		return $nextDealerRow[0];
	}

	/**
	 * Get the player id and turn that should make the next play after current. This method updates the game instance status.
	 * @param type $curTurnNum current turn number
	 */
	public function GetNextPlayerIdAndTurn($curTurnNum) {
		if (is_null($curTurnNum)) {
			$curTurnNum = -1;
		}
		//                   AND Status != '" . PlayerStatusType::LEFT. "'
		$query = "SELECT PlayerId, TurnNumber, Status, IsVirtual 
            FROM PlayerState
                WHERE GameInstanceId = $this->id AND TurnNumber > $curTurnNum
                ORDER BY TurnNumber LIMIT 1";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			$query = "SELECT PlayerId, TurnNumber, Status, IsVirtual
                    FROM PlayerState 
                    WHERE GameInstanceId = $this->id AND TurnNumber >= 0
                    ORDER BY TurnNumber LIMIT 1";
			$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		}
		if (mysql_num_rows($result) == 0) {
			throw new Exception(__FUNCTION__ . ": ERROR - Next PlayerState not found for 
                    instance id $this->id AND current seat $curTurnNum");
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);

		// recursively find the next player
		$nextTurn = $row['TurnNumber'];
		if ($row['Status'] != PlayerStatusType::FOLDED &&
				$row['Status'] != PlayerStatusType::LEFT) {
			$nextPlayerId = $row['PlayerId'];
			$nextPlayerStatus = PlayerInstance::GetPlayerInstance($this->id, $nextPlayerId);
			//$nextPlayerStatus->isVirtual = $row['IsVirtual'];
			$this->nextPlayerId = $nextPlayerId;
			//$this->nextTurnNumber = $nextTurn;
			return $nextPlayerStatus;
		} else {
			// keep looking, but increment play counters
			// is the next line correct? what's the point?
			//$this->playerInstanceStatus->lastPlayInstanceNumber +=1;
			$this->lastInstancePlayNumber +=1;
			if ($this->lastInstancePlayNumber < 4 * $this->numberPlayers) {
				$this->GetNextPlayerIdAndTurn($nextTurn);
			} else {
				$this->nextPlayerId = null;
			}
		}
	}

	/**
	 * New game instances don't have player info
	 * @param type $gameInstance
	 */
	public function Insert() {
		global $dateTimeFormat;

		$startDTQ = "'" . $this->startDateTime->format($dateTimeFormat) . "'";
		$updateDTQ = "'" . $this->lastUpdateDateTime->format($dateTimeFormat) . "'";
		// TODO: set null?
		$vars = "Id, GameSessionId, Status, StartDateTime, LastUpdateDateTime, "
				. "CurrentPotSize, LastBetSize, NumberCommunityCardsShown, "
				. "LastInstancePlayNumber";
		$values = "$this->id, $this->gameSessionId, '$this->status', $startDTQ, $updateDTQ, "
                . "$this->currentPotSize, $this->lastBetSize, $this->numberCommunityCardsShown, "
                . "$this->lastInstancePlayNumber";
		$event = "INSERT INTO GameInstance ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	/**
	 * Updates GameInstance and PlayerState with the results of the action.
	 * @param type $statusDT
	 */
	public function Update() {
		$statusDT = Context::GetStatusDTString();
		$status = is_null($this->status) ? 'null' : $this->status;
		$nextPlayerId = is_null($this->nextPlayerId) ? 'null' : $this->nextPlayerId;
		$currentPotSize = is_null($this->currentPotSize) ? 'null' : $this->currentPotSize;
		$lastBetSize = is_null($this->lastBetSize) ? 'null' : $this->lastBetSize;
		$cards = is_null($this->numberCommunityCardsShown) ? 'null' : $this->numberCommunityCardsShown;
		$playNumber = is_null($this->lastInstancePlayNumber) ? 'null' : $this->lastInstancePlayNumber;
		$winningPlayerId = is_null($this->winningPlayerId) ? 'null' : $this->winningPlayerId;
		// update in database
		// no need to save the next player turn number
		$vars = "LastUpdateDateTime, Status, NextPlayerId, CurrentPotSize, LastBetSize, "
				. "NumberCommunityCardsShown, LastInstancePlayNumber, WinningPlayerId";
		$values = "'$statusDT', '$statusDT', $nextPlayerId, $currentPotSize, $lastBetSize, "
				. "$cards, $playNumber, $winningPlayerId";
		$where = "Id = $this->id";
		$event = "UPDATE GameInstance SET LastUpdateDateTime = '$statusDT', "
				. "Status = '$status', "
				. "NextPlayerId = $nextPlayerId, "
				. "CurrentPotSize = $currentPotSize, "
				. "LastBetSize = $lastBetSize, "
				. "NumberCommunityCardsShown = $cards, "
				. "LastInstancePlayNumber = $playNumber, "
				. "WinningPlayerId = $winningPlayerId WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	public function Delete($row = null) {
		$where = "Id = $this->id";
		$event = "DELETE FROM GameInstance WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		if (is_null($row)) {
			$row = $this;
		}
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($row));
	}

	public static function DeleteExpiredInstances($endString) {
		ExpectedPokerMove::DeletedExpired($endString);
		GameCard::DeleteExpired($endString);
		PlayerInstance::DeleteExpired($endString);

		$query = "SELECT * FROM GameInstance WHERE LastUpdateDateTime <= '$endString'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$gameInstance = new GameInstance($row["Id"]);
			$gameInstance->Delete($row);
		}
	}

}

?>
