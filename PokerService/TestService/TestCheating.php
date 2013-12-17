<?php

function verifyUpdateSingleHandForPlayer($playerNumber, $pCardNum, $cheatedHands, $sleeves, $hCardNum, $indexNum) {
	global $playerHands;
	global $indexCards;

	$countFailed = 0;
	$cardCode = $sleeves[$hCardNum];
	$playerHands[$playerNumber][$pCardNum - 1] = $cardCode;
	$cardIndex = $playerNumber * 2 + $pCardNum - 1;
	$indexCards[$indexNum][$cardIndex] = $cardCode;
	//verify player card
	if ($cheatedHands->playerCardNumber != $pCardNum || $cheatedHands->cardCode != $cardCode) {
		$countFailed++;
		echo "*** FAILED: replaced wrong card or with wrong value: " . json_encode($cheatedHands) . "<br/>";
	}
	return $countFailed;
}

function verifyAllHandsForPlayer($cheatedCards, $i, $suitType = null) {
	global $printCheatingAPI;
	global $playerIds;
	global $itemTypeSuit;
	global $playerHands;
	global $playerIds;

	$countFailed = 0;
	$j = $i * 2;
	if ($suitType == null) {
		for ($cardIndex = 0; $cardIndex < 2; $cardIndex++) {
			if ($playerHands[$i][$cardIndex] != $cheatedCards[$j + $cardIndex]->cardCode) {
				$countFailed++;
				echo " *** FAILED: player $i id " . $playerIds[$i] . "should be "
				. $playerHands[$i][$cardIndex] . " but is " . $cheatedCards[$j + $cardIndex]->cardCode . " instead <br/>";
			}
		}
		return $countFailed;
	}

	$suitWord = $itemTypeSuit[$suitType];
	$suitLetter = substr($suitWord, 1, 1);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "Expected player hand for player number $i id " . $playerIds[$i];
		echo ": " . json_encode($playerHands[$i]) . "<br />";
	}
	for ($cardIndex = 0; $cardIndex < 2; $cardIndex++) {
		$cardNumber = $cardIndex + 1;
		if ($playerHands[$i][$cardIndex][1] == $suitLetter && $cheatedCards[$j]->suit != $suitWord) {
			$countFailed++;
			echo "*** FAILED: Incorrectly identified $suitWord on card $cardNumber for player " . $playerIds[$i] . ' expected ' . $playerHands[$i][0] . ' got ' . $cheatedCards[$j]->suit . '<br/><br/>';
		}
		$j++;
	}
	return $countFailed;
}

function verifyOtherPlayerCardCode($playerHands, $gameInstanceId, $playerNumber, $cardNumber) {
	global $printCheatingAPI;
	global $playerIds;

	connectToStateDB();

	$playerHandDto = CardHelper::getPlayerHandDto($playerIds[$playerNumber], $gameInstanceId);
	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "Player hand is: " . json_encode($playerHandDto) . '<br />';
	}
	if ($cardNumber == 1) {
		$otherCardNumber = 2;
		$actualOtherCard = $playerHandDto->pokerCard2Code;
		$expOtherCard = $playerHands[$playerNumber][1];
		// update changed card
		$playerHands[$playerNumber][0] = $playerHandDto->pokerCard1Code;
	} else {
		$otherCardNumber = 1;
		$actualOtherCard = $playerHandDto->pokerCard1Code;
		$expOtherCard = $playerHands[$playerNumber][0];
		$playerHands[$playerNumber][1] = $playerHandDto->pokerCard2Code;
	}
	if ($expOtherCard !== $actualOtherCard) {
		echo "*** FAILED: The other card number $otherCardNumber changed for player from [$expOtherCard] to [$actualOtherCard]. <br />";
		return 1;
	}
	return 0;
}

function verifyUpdateHiddenCards($hiddenList, &$sleeves, $hiddenCardNumber) {
	global $printCheatingAPI;
	$countFailed = 0;

	// udpdated expected
	array_splice($sleeves, $hiddenCardNumber, 1);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo " Expected hidden cards: " . json_encode($sleeves) . "<br/>";
		echo " Actual hidden cards: " . json_encode($hiddenList) . "<br />";
	}
	// verify hidden card
	if (count($hiddenList) != count($sleeves)) {
		$countFailed++;
		echo "*** FAILED: hidden card list mismatch expected " . json_encode($sleeves) . " got " . json_encode($hiddenList) . " instead<br/>";
	} else {
		$i = 0;
		foreach ($hiddenList as $hidden) {
			if ($hidden != $sleeves[$i]) {
				$countFailed++;
				echo "*** FAILED: expected hidden card " . $sleeves[$i] . " found $hidden instead <br/>";
			}
			$i++;
		}
	}

	// hidden cards after
	return $countFailed;
}

function verifyCheatedCardsKnownList($cheatedCards, $revealedCards) {
	if ($cheatedCards == null || count($revealedCards) != count($cheatedCards)) {
		echo "*** FAILED: expected " . json_encode($revealedCards)
		. " but got " . json_encode($cheatedCards) . " instead<br/>";
		return 1;
	}
	$countFailed = 0;

	//$nextCardCodes = array();
	foreach ($cheatedCards as $nCard) {
		//$nCardCode = array_search($nCard->cardName, $pokerCardName);
		//if (!in_array($nCardCode, $revealedCards)) {
		if (!in_array($nCard->cardCode, $revealedCards)) {
			$countFailed++;
			echo "*** FAILED: " . json_encode($nCard) . " not in expected list " . json_encode($revealedCards) . "<br/>";
		}
		//array_push($nextCardCodes, $nCardCode);
		//array_push($nextCardCodes, $nCard);
	}
	foreach ($revealedCards as $rCard) {
		$found = false;
		$i = 0;
		while (!$found && $i < count($cheatedCards)) {
			if ($rCard == $cheatedCards[$i]->cardCode) {
				$found = true;
			}
			$i++;
		}
		if (!$found) {
			$countFailed++;
			echo "*** FAILED: " . json_encode($rCard) . " should be revealed but not in actual list "
			. json_encode($cheatedCards) . "<br/>";
		}
	}
	return $countFailed;
}

function testCheatSuitMarker($playerNumber, $suitType) {
	global $playerIds;
	global $q;
	global $gameSessionId;
	global $gameInstanceId;
	global $activePlayers;

	echo "Testing suit marker $suitType";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br />";
	$countFailed = 0;

	queueCheatSuitMarker($playerIds[$playerNumber], $suitType, $gameSessionId, $gameInstanceId);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], $suitType, array(CheatDtoType::CheatedCards, CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];
	$cheatedCards = $eventData[0];
// verify suit correctly identified, assuming cheatedCards sorted
	for ($i = 0; $i < $activePlayers; $i++) {
		$countFailed += verifyAllHandsForPlayer($cheatedCards, $i, $suitType);
	}
	if ($countFailed === 0) {
		echo "PASSED suit marker test $suitType <br/><br/>";
	}
}

function testCheatAcePusherUpdateExpected($playerNumber, $cardNumber, $cardIndex) {
	global $playerIds;
	global $q;
	global $gameSessionId;
	global $gameInstanceId;
	global $indexCards;
	global $playerHands;

	echo "Testing ace pusher";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br />";
	$countFailed = 0;
	queueCheatAcePusher($playerIds[$playerNumber], $cardNumber, $gameSessionId, $gameInstanceId);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::ACE_PUSHER, array(CheatDtoType::CheatedHands, CheatDtoType::ItemLog), 0);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];
	$changedAceHand = $eventData[0];

	$cardCode = $changedAceHand->cardCode;
	if (!$changedAceHand->playerCardNumber != 1 && 'A' != substr($cardCode, 0, 1)) {
		$countFailed++;
		echo '*** FAILED Push Random Ace: wrong card number or ace not pushed ' . json_encode($changedAceHand) . '<br />';
	}

	// make sure the other hand didn't change 
	$countFailed += verifyOtherPlayerCardCode($playerHands, $gameInstanceId, $playerNumber, $cardNumber);

	// update expected
	$indexCards[$cardIndex][$playerNumber * 2 + ($cardNumber - 1)] = $cardCode;
	$playerHands[$playerNumber][$cardNumber - 1] = $changedAceHand->cardCode;

	if ($countFailed === 0) {
		echo "PASSED ace pusher test <br/><br/>";
	}
}

function testCheatUseSleeveUpdateExpected($playerNumber, $pCardNum, $hCardNum, $sleeves, $indexNum) {
	global $playerIds;
	global $q;

	$countFailed = 0;
	echo "Testing use card on sleeve <br/>";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";
	// informational:
	/* connectToStateDB();
	  $beforeCards = CardHelper::getPlayerHandDto($playerIds[$playerNumber], $gameInstanceId);
	  echo "Info - Player Cards before using card on sleeve " . json_encode($beforeCards) . "<br/>";
	  $hidden = new PlayerHiddenCards($playerIds[$playerNumber], null, ItemType::LOAD_CARD_ON_SLEEVE);
	  $hiddenCards = $hidden->GetSavedCardCodes();
	  echo "Info - Hidden card before using sleeve " . json_encode($hiddenCards) . "<br/>";
	 */
	queueCheatUseCardOnSleeve($playerIds[0], $pCardNum, $hCardNum);
	ConsumeTableQueue();

	/*
	  connectToStateDB();
	  $afterCards = CardHelper::getPlayerHandDto($playerIds[$playerNumber], $gameInstanceId);
	  echo "Info - Player cards after using card on sleeve " . json_encode($afterCards) . "<br/>";
	 */

	$isDisabled = 0;
	if (count($sleeves) == 0) {
		$isDisabled = 1;
	}
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::USE_CARD_ON_SLEEVE, array(CheatDtoType::CheatedHands, CheatDtoType::CheatedHidden, CheatDtoType::ItemLog), $isDisabled);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];

	$cheatedHands = $eventData[0];
	$hiddenList = $eventData[1];
	//echo "Info - Event Data should be array of 2 $eventData<br/>";

	$countFailed += verifyUpdateSingleHandForPlayer($playerNumber, $pCardNum, $cheatedHands, $sleeves, $hCardNum, $indexNum);

	$countFailed += verifyUpdateHiddenCards($hiddenList, $sleeves, $hCardNum);

	if ($countFailed === 0) {
		echo "PASSED use sleeve card test<br/><br/>";
	}
}

function testCheatRiverLook($playerNumber) {
	global $gameSessionId;
	global $gameInstanceId;
	global $playerIds;
	global $q;
	global $communityCards;

	echo "Testing river shuffler";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	//connectToStateDB();
	//$gameCards = new GameInstanceCards($gameInstanceId);
	//$cCards = $gameCards->GetSavedCommunityCardDtos(5);

	queueCheatRiverShuffler($playerIds[$playerNumber], $gameSessionId, $gameInstanceId);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::RIVER_SHUFFLER, array(CheatDtoType::CheatedNext, CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];
	$cheatedCards = $eventData[0];

	if ($cheatedCards[0] != $communityCards[4]) {
		$countFailed++;
		echo "*** FAILED: Expected river card to be " . $communityCards[4] .
		" found " . $cheatedCards[0] . " instead<br/>";
	}
	if ($countFailed === 0) {
		echo "PASSED cheat river look test.<br/><br/>";
	}
}

function testCheatRiverUse($playerNumber, $cardIndex) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;
	global $playerIds;
	global $q;
	global $communityCards;
	global $indexCards;
	global $startingPlayers;

	echo "Testing river shuffler use - swap river";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	queueCheatRiverShufflerUse($playerIds[$playerNumber], $gameSessionId, $gameInstanceId);
	ConsumeTableQueue();
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::RIVER_SHUFFLER_USE, array(CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];

	connectToStateDB();
	$gameCards = new GameInstanceCards($gameInstanceId);
	$actualCards = $gameCards->GetSavedCommunityCardCodes(5);

	// update expected community cards
	$newRiverIndex = $startingPlayers * 2 + 5;
	$newRiverCard = $indexCards[$cardIndex][$newRiverIndex];
	array_splice($communityCards, -1, 1, $newRiverCard);
	$indexCards[$cardIndex][$newRiverIndex] = $indexCards[$cardIndex][$newRiverIndex - 1];
	$indexCards[$cardIndex][$newRiverIndex - 1] = $newRiverCard;

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "Info - Community card after river shuffler: " . json_encode($communityCards) . "<br />";
	}
	for ($i = 0; $i < 5; $i++) {
		if ($actualCards[$i] != $communityCards[$i]) {
			$countFailed++;
			echo "*** FAILED: community card $i expected " . $communityCards[$i] .
			" but is " . $actualCards[$i] . " <br/>";
		}
	}
	if ($countFailed === 0) {
		echo "PASSED Cheat River Use <br/><br/>";
	}
}

function testCheatStartSocialSpotter($playerNumber) {
	global $playerIds;
	global $q;
	//global $gameSessionId;

	echo "Testing social spotter activation";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	/*
	  connectToStateDB();
	  // start card marker
	  $visibles = new PlayerVisibleCards($playerIds[2], $gameSessionId);
	  $visibleList = $visibles->GetSavedCardCodes();
	  echo "Info - Visible card codes: " . json_encode($visibleList) . "<br/>";
	 */
	queueCheatStartCardMarking($playerIds[$playerNumber]);
	ConsumeTableQueue();
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::SOCIAL_SPOTTER, array(CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];

	if ($countFailed === 0) {
		echo "PASSED social spotter (request only)<br/><br/>";
	}
}

function testCheatSocialSpotterWorks($playerNumber, $previousGameCards, $indexCards) {
	global $printCheatingAPI;
	global $playerIds;
	global $q;
	global $startingPlayers;

	echo "Testing social spotter on game start";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	// find on current index cards which ones overlap
	if ($previousGameCards != null) {
		// exclude player Number
		$playerCards = array();
		//$playerCards = array_slice($indexCards, 0, $startingPlayers * 2);
		for ($i = 0; $i<$startingPlayers *2; $i++) {
			if ($i !== $playerNumber*2 && $i !== $playerNumber*2 + 1) {
				array_push($playerCards, $indexCards[$i]);
			}
		}
		$intersect = array_intersect($previousGameCards, $playerCards);
		$revealedCards = array_values($intersect);
	} else {
		$revealedCards = array();
	}
	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "Cards from previous game: " . json_encode($previousGameCards) . "<br/>";
		echo " - Cards that should be revealed: " . json_encode($revealedCards) . "<br/>";
	}
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::SOCIAL_SPOTTER, array(CheatDtoType::CheatedCards, CheatDtoType::ItemLog), null);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];

	$cheatedCards = $eventData[0];
	$countFailed += verifyCheatedCardsKnownList($cheatedCards, $revealedCards);
	if ($countFailed == 0) {
		echo "PASSED social spotting test<br/><br/>";
	}
}

function testCheatOilMarkerWorks($playerNumber, $indexCards) {
	//global $printCheatingAPI;
	global $playerIds;
	global $q;
	//global $startingPlayers;

	echo "Testing oil marker works on game start";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::SNAKE_OIL_MARKER, array(CheatDtoType::CheatedCards, CheatDtoType::ItemLog), null);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];

	$cheatedCards = $eventData[0];
	// all should have been revealed
	$revealedCards = array();
	for ($i = 0; $i<count($cheatedCards); $i++) {
		array_push($revealedCards, $cheatedCards[$i]->cardCode);
	}
	$countFailed += verifyCheatedCardsKnownList($cheatedCards, $revealedCards);
	if ($countFailed == 0) {
		echo "PASSED oil marker test<br/><br/>";
	}
}

function testPokerPeeker($playerNumber, $otherPlayerNumber, $otherPlayerCardNumber) {
	global $playerIds;
	global $q;
	global $playerHands;

	echo "Testing poker peeker";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	queuePokerPeeker($playerIds[$playerNumber], $playerIds[$otherPlayerNumber], $otherPlayerCardNumber);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::POKER_PEEKER, array(CheatDtoType::CheatedCards, CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];

	$card1 = $eventData[0][0];
	$card1Code = $playerHands[$otherPlayerNumber][$otherPlayerCardNumber-1];
	if ($card1->cardCode != $card1Code) {
		$countFailed++;
		echo "*** FAILED poker peeker card $otherPlayerNumber was " . $card1->cardCode . " instead of $card1Code<br/>";
	}
	if ($countFailed == 0) {
		echo "PASSED poker peeker test<br/><br/>";
	}
}

function verifyHiddenList($expectedList, $actualList) {
	echo "Expected hidden list: " . json_encode($expectedList) . "<br/>";
	echo "Actual hidden list: " . json_encode($actualList) . "<br/>";
	if (count($expectedList) != count($actualList)) {
		echo "*** FAILED: expected and actual hidden list are not the same length <br />";
		return 1;
	}
	$i = 0;
	$countFailed = 0;
	for ($i = 0; $i < count($actualList); $i++) {
		if (!in_array($actualList[$i], $expectedList)) {
			$countFailed++;
			echo "**** FAILED: actual " . $actualList[$i] . " not in " . json_encode($expectedList) . "<br/>";
		}
		if (!in_array($expectedList[$i], $actualList)) {
			$countFailed++;
			echo "**** FAILED: expected " . $expectedList[$i] . " not in " . json_encode($actualList) . "<br/>";
		}
	}
	return $countFailed;
}

function testCheatTableTuckerLoad($playerNumber, $cards) {
	global $playerIds;
	global $q;
	global $tableGroove;

	echo "Testing table tucker load";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	queueCheatTuckLoad($playerIds[$playerNumber], $cards);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::TUCKER_TABLE_SLIDE_UNDER, array(CheatDtoType::CheatedHidden, CheatDtoType::ItemLog), 0);
	// TODO: isDisabled=0 hard coded
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];
	$cheatedHidden = $eventData[0];

	// verify hidden list
	$countFailed += verifyHiddenList($tableGroove, $cheatedHidden);
	if ($countFailed == 0) {
		echo "PASSED table tucker load test<br/><br/>";
	}
}

function testCheatTableTuckerUse($playerNumber, $cardNumber, $hiddenNumber) {
	global $printCheatingAPI;
	global $playerIds;
	global $q;
	global $tableGroove;

	echo "Testing table tucker use";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/>";

	$countFailed = 0;
	queueCheatTuckUse($playerIds[$playerNumber], $cardNumber, $hiddenNumber);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::TUCKER_TABLE_EXCHANGE, array(CheatDtoType::CheatedHands, CheatDtoType::CheatedHidden, CheatDtoType::ItemLog), 0);
	$countFailed += $msg['countFailed'];
	$eventData = $msg['eventData'];

	$newHands = $eventData[0];
	$hiddenList = $eventData[1];
	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "Output (new hand) is: " . json_encode($newHands) . "<br />";
		echo "Output (list of hidden cards) is: " . json_encode($hiddenList) . "<br/>";
	}
	// verify hidden list
	$countFailed += verifyUpdateHiddenCards($hiddenList, $tableGroove, $hiddenNumber);
	if ($countFailed == 0) {
		echo "PASSED table tucker USE test<br/><br/>";
	}
}

function testCheatStartOilMarker($playerNumber) {
	global $playerIds;
	global $q;

	echo "Testing oil marker";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	queueCheatStartOilMarker($playerIds[$playerNumber]);
	ConsumeTableQueue();
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::SNAKE_OIL_MARKER, array(CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];

	if ($countFailed === 0) {
		echo "PASSED oil marker test<br/><br/>";
	}
}

function verifyCounteredOilMarker($castingPlayerNumber, $markedPlayerNumber, $cheatedCards) {
	global $gameSessionId;
	global $playerIds;
	global $startingPlayers;
	global $playerHands;

	connectToStateDB();
	$visibles = new PlayerVisibleCards($playerIds[$markedPlayerNumber], $gameSessionId, ItemType::SNAKE_OIL_MARKER_COUNTERED);
	$visibleList = $visibles->GetSavedCardCodes();
	echo "Info - Visible card codes: " . json_encode($visibleList) . "<br/>";

	$countFailed = 0;
	$revealedList = array();
	// compare revealed
	for ($i = 0; $i < count($cheatedCards); $i++) {
		if (!in_array($cheatedCards[$i]->cardCode, $visibleList)) {
			$countFailed++;
			echo " *** FAILED: " . $cheatedCards[$i]->cardCode . " should not have been revealed <br>";
		}
		array_push($revealedList, $cheatedCards[$i]->cardCode);
	}

	// compare not revealed
	for ($i = 0; $i < $startingPlayers; $i++) {
		if ($i != $markedPlayerNumber && !in_array($playerHands[$i][0], $revealedList) &&
				in_array($playerHands[$i][0], $visibleList)) {
			$countFailed++;
			echo " *** FAILED: " . $playerHands[$i][0] . " should have been revealed <br>";
		}
		if ($i != $markedPlayerNumber && !in_array($playerHands[$i][1], $revealedList) &&
				in_array($playerHands[$i][1], $visibleList)) {
			$countFailed++;
			echo " *** FAILED: " . $playerHands[$i][0] . " should have been revealed <br>";
		}
	}
	return $countFailed;
}

function testCheatAntiOilMarker($playerNumber, $otherPlayerNumber, $otherPlayerResponseFlag) {
	global $playerIds;
	global $q;

	$countFailed = 0;
	echo "Testing anti oil marker ";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber];
	echo " on player # $otherPlayerNumber id " . $playerIds[$otherPlayerNumber] . " <br/>";

	queueCheatAntiOilMarker($playerIds[$playerNumber], $playerIds[$otherPlayerNumber]);
	ConsumeTableQueue();

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::ANTI_OIL_MARKER, array(CheatDtoType::ItemLog), 1);
	$countFailed += $msg['countFailed'];

	if ($otherPlayerResponseFlag) {
		// check other player
		$otherMsg = verifyQCheatingMessage($playerIds[$otherPlayerNumber], $q[$otherPlayerNumber], ItemType::SNAKE_OIL_MARKER, array(CheatDtoType::CheatedCards, CheatDtoType::ItemLog), 0);
		$countFailed += $otherMsg['countFailed'];
		$otherEvent = $otherMsg['eventData'];

		$cheatedCards = $otherEvent[0];
		echo "Output (cheated card list) is " . json_encode($cheatedCards) . "<br />";
		$countFailed += verifyCounteredOilMarker($playerNumber, $otherPlayerNumber, $cheatedCards);
	}
	if ($countFailed == 0) {
		echo "PASSED anti oil marker test<br/><br/>";
	}
}

/**
 * At beginning of game, verify oil marker
 * @param type $playerNumber
 * @param type $otherPlayerNumber
 */
function testCheatAntiOilMarkerWorks($playerNumber, $otherPlayerNumber) {
	global $playerIds;
	global $q;

	echo "Testing anti oil marker works ";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber];
	echo " on player # $otherPlayerNumber id " . $playerIds[$otherPlayerNumber] . " <br/>";

	$countFailed = 0;
	// check other player
	$otherMsg = verifyQCheatingMessage($playerIds[$otherPlayerNumber], $q[$otherPlayerNumber], ItemType::SNAKE_OIL_MARKER, array(CheatDtoType::CheatedCards, CheatDtoType::ItemLog), null);
	$countFailed += $otherMsg['countFailed'];
	$otherEvent = $otherMsg['eventData'];
	$cheatedCards = $otherEvent[0];
	echo "Output (cheated card list) is " . json_encode($cheatedCards) . "<br />";
	$countFailed += verifyCounteredOilMarker($playerNumber, $otherPlayerNumber, $cheatedCards);
	
	// verify casting player receives a message also
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::ANTI_OIL_MARKER, array(CheatDtoType::ItemLog), null);
	$countFailed += $msg['countFailed'];

	if ($countFailed == 0) {
		echo "PASSED anti oil marker works on new game start test<br/><br/>";
	}
}

function testCheatFaceCard($playerNumber) {
	global $playerIds;
	global $q;
	queueCheatFaceCards($playerIds[$playerNumber]);
	ConsumeTableQueue();

	echo "Testing keep face cards ";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	$countFailed = 0;
	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::KEEP_FACE_CARDS, array(CheatDtoType::ItemLog), 0);
	$countFailed += $msg['countFailed'];

	if ($countFailed === 0) {
		// TODO: not true, isDisabled may have failed
		echo "PASSED keep face card test (request only)<br/><br/>";
	}
}

function testCheatFaceCardWorks($playerNumber, $cardIndex) {
	global $playerIds;
	global $q;
	global $startingPlayers;
	global $gameInstanceId;

	$countFailed = 0;
	echo "Testing keep face card works ";
	echo " for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/>";

	$msg = verifyQCheatingMessage($playerIds[$playerNumber], $q[$playerNumber], ItemType::KEEP_FACE_CARDS, array(CheatDtoType::ItemLog), 0);
	$countFailed += $msg['countFailed'];

	// player hands should be updated at the beginning of the game
	//UpdatePlayerHandsFaceCard($playerNumber, $cardIndex);

	connectToStateDB();
	$cheatedCards = array();
	$i = 0;
	$j = 0;
	while ($i < $startingPlayers * 2) {
		//$gameCards = new GameInstanceCards($gameInstanceId);
		//$cCards = $gameCards->GetSavedCommunityCardDtos(5);
		$hands = CardHelper::getPlayerHandDto($playerIds[$j], $gameInstanceId);
		$cheatedCards[$i++] = new PlayerCardDto($playerIds[$j], 1, $hands->pokerCard1Code, null);
		$cheatedCards[$i++] = new PlayerCardDto($playerIds[$j++], 2, $hands->pokerCard2Code, null);
	}
	for ($i = 0; $i < $startingPlayers; $i++) {
		$countFailed += verifyAllHandsForPlayer($cheatedCards, $i);
	}
	if ($countFailed == 0) {
		echo "PASSED keeping face card works on game start<br/><br/>";
	}
}

// swap first face card not for user with user's first non-face card. No change if user has two face cards
function UpdatePlayerHandsFaceCard($playerNumber, $cardIndex) {
	global $playerHands;
	global $communityCards;
	global $startingPlayers;
	global $indexCards;
	$playerCardNumber = null;
	$faceCards = array('J', 'Q', 'K');
	// find which user hand to replace
	if (!in_array($playerHands[$playerNumber][0][0], $faceCards)) {
		$playerCardNumber = 0;
	} else if (!in_array($playerHands[$playerNumber][1][0], $faceCards)) {
		$playerCardNumber = 1;
	}
	if ($playerCardNumber === null) {
		// both cards are face cards, nothing to replace
		return;
	}
	$replacedPlayerCard = $playerHands[$playerNumber][$playerCardNumber];
	// go through all player hands then community cards then remaining index to find face card
	$faceIndex = null;
	$i = 0;
	while ($faceIndex === null && $i < $startingPlayers) {
		if ($i == $playerNumber) {
			$i++;
			continue;
		}
		for ($m = 0; $m < 2; $m++) {
			if ($faceIndex !== null) {continue;}
			if (in_array($playerHands[$i][$m][0], $faceCards)) {
				$faceIndex = $i * 2 + $m;
				$playerHands[$playerNumber][$playerCardNumber] = $playerHands[$i][$m];
				$playerHands[$i][$m] = $replacedPlayerCard;
			}
		}
		$i++;
	}
	$j = 0;
	while ($faceIndex === null && $j < 5) {
		if (in_array($communityCards[$j][0], $faceCards)) {
			$faceIndex = $startingPlayers * 2 + $j;
			$playerHands[$playerNumber][$playerCardNumber] = $communityCards[$j];
			$communityCards[$i] = $replacedPlayerCard;
		}
		$j++;
	}
	$k = $startingPlayers * 2 + 5;
	while ($faceIndex === null & $k < 52) {
		if (in_array($indexCards[$cardIndex][$faceIndex][0], $faceCards)) {
			$faceIndex = $k++;
		}
	}
	if ($faceIndex === null) {
		echo " BAD DATA, there are no face cards on index card $cardIndex <br/>";
		exit;
	}
	$indexCards[$cardIndex][$playerNumber * 2 + $playerCardNumber] = $indexCards[$cardIndex][$faceIndex];
	$indexCards[$cardIndex][$faceIndex] = $replacedPlayerCard;
}

?>
 