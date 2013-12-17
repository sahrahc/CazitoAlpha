<?php

class ExpectedPokerMove {

	public $gameInstanceId;
	public $playerId;
	public $expirationDate;
	public $callAmount;
	public $isCheckAllowed;
	public $raiseAmount;
	// not on db
	public $status;
	private $history;

	public function __construct() {
		$this->history = Logger::getLogger(__CLASS__);
	}

	/*	 * ****************************************************************************** */

	/**
	 * Define the first move on a newly started game instance.
	 * @global type $dateTimeFormat
	 * @global type $playExpiration
	 * @global type $practiceExpiration
	 * @param type $firstPlayerId
	 * @param type $tableMin
	 */
	public static function InitFirstMoveConstraints($gameInstance, $tableMin, $isPractice = 0) {
		global $playExpiration;
		global $practiceExpiration;
		$expirationDateTime = new DateTime();
		if ($isPractice == 1) {
			$expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
		} else {
			$expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
		}

		$raiseAmount = $tableMin * 2;
		$pokerMove = new ExpectedPokerMove();
		$pokerMove->gameInstanceId = $gameInstance->id;
		$pokerMove->playerId = $gameInstance->firstPlayerId;
		$pokerMove->expirationDate = $expirationDateTime;
		$pokerMove->callAmount = $tableMin;
		$pokerMove->raiseAmount = $raiseAmount;
		$pokerMove->Insert();

		return $pokerMove;
	}

	/**
	 * After a player makes a move, this operation is called to retrieve 
	 * the constraints on the next player's
	  moves. The next move is saved so that it can be expired.
	 * Restrictions: updateNextPlayerIdAndTurn must have been called before.
	 * @return ExpectedPokerMove
	 */
	public static function FindNextExpectedMoveForInstance($gameInstance, $curTurn) {
		global $playExpiration;
		global $practiceExpiration;
		global $defaultTableMin;
		$pokerMove = new ExpectedPokerMove();

		$gameInstance->GetNextPlayerIdAndTurn($curTurn);
		if (is_null($gameInstance->nextPlayerId)) {
			return null;
		}
		$expirationDateTime = new DateTime();
		$player = Player::GetPlayer($gameInstance->nextPlayerId);
		if ($player->isVirtual) {
			$expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
		} else {
			$expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
		}

		// 2 - parse out the next move
		// no need to set identifier, auto incrementing id
		$pokerMove->gameInstanceId = $gameInstance->id;
		$pokerMove->playerId = $gameInstance->nextPlayerId;
		$pokerMove->expirationDate = $expirationDateTime;

		// find move sizes - instance has not updated yet.
		// see if check allowed ---------------------------------------
		// Rule: not allowed on first round except for player who placed blind bet.
		$pokerMove->isCheckAllowed = 0;
		if ($gameInstance->lastInstancePlayNumber >= $gameInstance->numberPlayers - 1) {
			$pokerMove->isCheckAllowed = 1;
		}
		// call size  -----------------------------------------------
		$pokerMove->callAmount = $gameInstance->lastBetSize;

		// see how much raise is enabled by ---------------------------
		// Rule: first player on first round can only raise by 2*bigblind, but that is taken
		// care of on initFirstMove.
		$tableMin = $defaultTableMin;
		$casinoTable = CasinoTable::GetCasinoTableForSession($gameInstance->gameSessionId);
		if (!is_null($casinoTable)) {
			$tableMin = $casinoTable->tableMinimum;
		} else {
			$practiceSession = GameSession::GetGameSession($gameInstance->gameSessionId);
			if (!is_null($practiceSession)) {
				$tableMin = $practiceSession->tableMinimum;
			}
		}
		//$pokerMove->raiseAmount = $tableMin + $gameInstance->lastBetSize;
		$pokerMove->raiseAmount = 2 * $gameInstance->lastBetSize;

		$pokerMove->Insert();
		return $pokerMove;
	}

	/**
	 * Retrieves the next move but checks whether the user left.
	 * @param type $gInstanceId
	 * @return ExpectedPokerMove
	 */
	public static function GetExpectedMoveForInstance($gInstanceId) {
		$query = "SELECT e.*, s.Status FROM ExpectedPokerMove e
            INNER JOIN PlayerState s on e.Playerid = s.PlayerId
            WHERE e.GameInstanceId = $gInstanceId
                ORDER BY ExpirationDate DESC LIMIT 1";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$pokerMove = new ExpectedPokerMove();
		$pokerMove->mapRow($row);
		return $pokerMove;
	}

	public static function GetExpiredPokerMoves($expirationDateTime) {
		// check if expiration
		$query = "SELECT m.*, s.IsVirtual, s.Status
            FROM ExpectedPokerMove m INNER JOIN PlayerState s
            ON m.gameInstanceId = s.GameInstanceId AND m.PlayerId = s.PlayerId
			INNER JOIN GameSession gs on gs.Id = s.GameSessionId
            WHERE m.ExpirationDate <= '$expirationDateTime'
                ORDER BY m.GameInstanceId, m.ExpirationDate DESC";
		// only the last record for every game instance id is processed
		// this won't be needed when only one move is stored (out of database)
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$expectedPokerMoves = null;
		echo mysql_num_rows($result) . " rows found. <br />";
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$expectedPokerMoves[$i] = new ExpectedPokerMove();
			$expectedPokerMoves[$i]->mapRow($row);
			$i++;
		}
		return $expectedPokerMoves;
	}

	private function mapRow($row) {
		global $dateTimeFormat;
		$this->gameInstanceId = (int) $row["GameInstanceId"];
		$this->playerId = (int) $row["PlayerId"];
		$this->expirationDate = DateTime::createFromFormat($dateTimeFormat, $row["ExpirationDate"]);
		$this->callAmount = $row["CallAmount"];
		$this->isCheckAllowed = $row["CheckAmount"];
		$this->raiseAmount = $row["RaiseAmount"];
		$this->status = $row["Status"];
	}

	public function Insert() {
		global $dateTimeFormat;
		$checkAmt = 0;
		if (is_null($this->isCheckAllowed)) {
			$checkAmt = 'null';
		}
		$expirationString = "'" . $this->expirationDate->format($dateTimeFormat) . "'";

		$vars = "GameInstanceId, PlayerId, ExpirationDate, CallAmount, CheckAmount, RaiseAmount";
		$values = "$this->gameInstanceId, $this->playerId, $expirationString, "
				. "$this->callAmount, $checkAmt, $this->raiseAmount";
		$event = "INSERT INTO ExpectedPokerMove ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	public function Delete() {
		$where = "playerId = $this->playerId AND GameInstanceId = $this->gameInstanceId";
		$event = "DELETE FROM ExpectedPokerMove WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($this));
	}

	private static function getExpired($endString) {
		$query = "SELECT e.*, p.Status Status "
				. "FROM ExpectedPokerMove e "
				. "LEFT JOIN PlayerState p on e.PlayerId = p.PlayerId and e.GameInstanceId = p.GameInstanceId "
				. "WHERE e.GameInstanceId in
            (SELECT Id FROM GameInstance WHERE LastUpdateDateTime <= '$endString')";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);

		// only the last record for every game instance id is processed
		// this won't be needed when only one move is stored (out of database)
		$i = 0;
		$moves = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$moves[$i] = new ExpectedPokerMove();
			$moves[$i]->mapRow($row);
			$i++;
		}
		return $moves;
	}

	/**
	 * Preparing for obsolete instance to be deleted
	 * @param type $endString
	 */
	public static function DeletedExpired($endString) {
		$expiredMoves = self::getExpired($endString);

		if (!is_null($expiredMoves)) {
			foreach ($expiredMoves as $move) {
				$move->Delete();
			}
		}
	}
	/**
	 * Called by timer if player did not make a move
	 * Same as processActionFindNext but there was no action. Skipping a turn increments playnumber in instance and player state
	 * 1) No need to validate move
	 * 2) Increment timeout and set status to skipped for the player
	 * 3) Find next player
	 */
	function SkipTurn($gameInstance) {
		// initialize playerstatus
		$playerInstanceStatus = PlayerInstance::GetPlayerInstance($gameInstance->id, $this->playerId);
		if ($playerInstanceStatus->status !== PlayerStatusType::LEFT) {
			$playerInstanceStatus->status = PlayerStatusType::SKIPPED;
		}
		// increment time out
		$playerInstanceStatus->numberTimeOuts += 1;
		// fields that automatically update
		// update player status and player number - last play amount doesn't change
		//if ($playerInstanceStatus->status != PlayerStatusType::LEFT) {
		// setting to LEFT by calling function
		/* } 
		  if ($playerInstanceStatus->numberTimeOuts >= 3) {
		  $playerInstanceStatus->status = PlayerStatusType::LEFT;
		  } else {
		  $playerInstanceStatus->status = PlayerStatusType::SKIPPED;
		  } */
		// update instance last play number;
		$gameInstance->lastInstancePlayNumber +=1;
		$playerInstanceStatus->lastPlayInstanceNumber = $gameInstance->lastInstancePlayNumber;

		// player status stake and last player amount does not change
		// game instance pot size and last bet size does not change.
		// update player status
		$playerInstanceStatus->Update();

		$this->Delete();

		return $playerInstanceStatus;
	}


}
?>
s