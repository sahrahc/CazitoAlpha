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
class CheatOutcomeDto {

    public $dtoType; // string
    public $dto;
    
    function __construct($dtoType, $dto) {
        $this->dtoType = $dtoType;
        $this->dto = $dto;
    }
}
?>
