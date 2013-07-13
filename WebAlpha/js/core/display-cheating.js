/********************************************************************************************/
/**
 * Show the back of game cards for only the players who are actively in the game
 */
function hideCardMarkers() {
    S('player0Card1Marker').display = 'none';
    S('player0Card2Marker').display = 'none';

    S('player1Card1Marker').display = 'none';
    S('player1Card2Marker').display = 'none';

    S('player2Card1Marker').display = 'none';
    S('player2Card2Marker').display = 'none';

    S('player3Card1Marker').display = 'none';
    S('player3Card2Marker').display = 'none';

}

function resetCheatingOnGameStart() {
    // cheating items - does not apply for cheating
    O("nextCard").empty();
    O("nextCard").append("<p>Next Card:</ p>");
    O('LookRiverCard-look').disabled = false;
    O('LookRiverCard-swap').disabled = true;    
}

function disableInstanceItems(boolValue) {
    if (boolValue) {
        dimItem('AcePusher-Act');
        dimItem('HeartMarker-Act');
        dimItem('ClubMarker-Act');
        dimItem('DiamondMarker-Act');
        dimItem('LookRiverCard-Act');
        dimItem('PokerPeeker-Act');
        dimItem('RiverbendRedo-Act');
    }
    else {
        unDimItem('AcePusher-Act');
        unDimItem('HeartMarker-Act');
        unDimItem('ClubMarker-Act');
        unDimItem('DiamondMarker-Act');
        unDimItem('LookRiverCard-Act');
        unDimItem('PokerPeeker-Act');
        unDimItem('RiverbendRedo-Act');
    }
    O('AcePusher-Act').disabled = boolValue;
    O('HeartMarker-Act').disabled = boolValue;
    O('ClubMarker-Act').disabled = boolValue;
    O('DiamondMarker-Act').disabled = boolValue;
    O('LookRiverCard-Act').disabled = boolValue;
    O('PokerPeeker-Act').disabled = boolValue;
    /* session level
     O('TableTucker-Act').disabled = false;
     O('SocialSpotter-Act').disabled = false;
     O('SnakeOilMarker-Act').disabled = false;
     O('AntiOilMarker-Act').disabled = false;
     O('FaceMelter-Act').disabled = false;
     */
    O('RiverbendRedo-Act').disabled = boolValue;

}
