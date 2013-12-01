<?php

/**
 * dto types: 
 *  CheatDtoType::CheatedCards - CheaterCardDto[]
 *  CheatDtoType::CheatedHidden - char(2)[] (2-char card codes array)
 *  CheatDtoType::CheatedHands - CheaterCardDto
 *  CheatDtoType::CheatedNext - char(2)
 *  CheatDtoType::Item[End/Lock/Unlock/Log]
 *      string - message (ItemType ...)
 */
class CheatInfoDto {

    public $info; // string
	public $isDisabled;
    
    function __construct($info, $isDisabled = 0) {
        $this->info = $info;
		$this->isDisabled = $isDisabled;
    }
}
?>
