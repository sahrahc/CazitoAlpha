canvas = O('play-section-canvas');
context = canvas.getContext("2d");

// the following wouldn't be necessary if using enclosing function
counter = null;
startX = null;
startY = null;
endX = null;
endY = null;
speedX = null;
speedY = null;
cardQueue = [];
currentCard = null;
constCardNormalWidth = 43;
constCardNormalHeight = 60;

/*
 * shows the specified community card
 */
function showCommunityCards(communityCards) {
    for (var j = 0, m = communityCards.length; j < m; j++) {
        // the cards are ordered, so the index is the position
        card = {
            position: j,
            image: O('communityCard' + j),
            playerId: -1,
            value: communityCards[j]
        };
        card.image.src = "../../../images/" + "PokerCard_" + communityCards[j] + "_small.png";
        cardQueue.push(card);
    }
}

function animateCard() {
    // ready to draw new card
    if (counter === null) {

        if (cardQueue.length === 0) {
            // check again a a little later
            setTimeout(animateCard, 500);
        }
        else {
            counter = 0;
            currentCard = cardQueue.shift();
            // initialize
            var dealerPlayerTag = getPlayerPositionTag(O('currentDealerId').innerHTML);
            // get dealer button
            var dealerButtonStyle = $('#' + dealerPlayerTag + 'DealerButton');
            startX = getSize(dealerButtonStyle.css('left'));
            startY = getSize(dealerButtonStyle.css('top'));
            if (currentCard.playerId === -1) {
                endX = getSize($('#communityCard' + currentCard.position).css('left'));
                endY = getSize($('#communityCard' + currentCard.position).css('top'));
            }
            speedX = (endX - startX) / 30;
            speedY = (endY - startY) / 30;

            setTimeout(drawCard, 20);
        }
    }
    // still animating a card
    else {
        setTimeout(drawCard, 20);
    }
}
function drawCard() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    counter++;
    if (counter < 30) {

        context.drawImage(currentCard.image, startX, startY, constCardNormalWidth, constCardNormalHeight);
        //img.src = currentCard.image.src;
        startX += speedX;
        startY += speedY;
        setTimeout(drawCard, 30);
    }
    else if (counter >= 30) {
        counter = null;
        currentCard.image.style.display = 'block';
        //O('communityCard' + cardPosition).src = "../Images/" + "PokerCard_" + cardValue + "_small.png";

        //S('communityCard' + cardPosition).display = 'block';
        // get next
        setTimeout(animateCard, 30);
    }
}
/** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Calculate the chip sizes and colors appropriate for the bet amount and display them
 */
function showChips(playerTag, betAmount) {
}

/**
 * animation for moving a chip from a player to the pot
 */
function moveChips() {
    context.save();
    // get list of all blind bets
    // loop to move incrementally
    context.rotate(Math.PI / 20);
    context.restore();
}

